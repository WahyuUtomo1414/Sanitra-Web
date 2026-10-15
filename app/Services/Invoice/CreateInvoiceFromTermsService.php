<?php

namespace App\Services\Invoice;

use App\Enums\AutoCodePrefix;
use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\ProjectStageStatus;
use App\Enums\Status\TermStatus;
use App\Enums\VatType;
use App\Models\MsBankAccount;
use App\Models\MsStatus;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesOrderHd;
use App\Services\Shared\GenerateCodeService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateInvoiceFromTermsService
{
    public function __construct(public GenerateCodeService $generateCode) {}

    /**
     * @param  array<int, int|string>  $termIds
     * @return array{subtotal: string, vat_amount: string, grand_total: string}
     */
    public function preview(TransSalesOrderHd $order, array $termIds): array
    {
        $termIds = array_map('intval', $termIds);
        $grandTotal = $order->terms()->whereKey($termIds)->get(['amount'])->reduce(
            fn (string $carry, $term): string => bcadd($carry, (string) $term->amount, 6),
            '0.000000',
        );

        return $this->calculateAmounts($order, $grandTotal, $termIds);
    }

    /** @param array{term_ids: array<int, int|string>, transaction_date: string, due_date: string, bank_account_id: int|string, notes?: string|null} $data */
    public function execute(TransSalesOrderHd $order, array $data): TransSalesInvoiceHd
    {
        $data = Validator::make($data, [
            'term_ids' => ['required', 'array', 'min:1'],
            'term_ids.*' => ['required', 'integer', 'distinct'],
            'transaction_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:transaction_date'],
            'bank_account_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($order, $data): TransSalesInvoiceHd {
            $order = TransSalesOrderHd::query()->lockForUpdate()->findOrFail($order->id);

            if (! MsBankAccount::query()->whereKey($data['bank_account_id'])->where('active', true)->exists()) {
                throw new DomainException('Rekening bank aktif wajib dipilih.');
            }

            $termIds = array_map('intval', $data['term_ids']);
            $terms = $order->terms()->whereKey($termIds)->with('status')->lockForUpdate()->get();

            if ($terms->count() !== count($termIds)) {
                throw new DomainException('Semua termin harus berasal dari Sales Order ini.');
            }

            $grandTotal = '0.000000';

            foreach ($terms as $term) {
                if ($term->status?->code !== TermStatus::ReadyToBill->value) {
                    throw new DomainException('Hanya termin Ready to Bill yang dapat ditagih.');
                }

                $hasActiveInvoice = $term->invoiceDetails()
                    ->whereHas('invoice.status', fn (Builder $query): Builder => $query
                        ->where('type', InvoiceStatus::type()->value)
                        ->where('code', '!=', InvoiceStatus::Void->value))
                    ->exists();

                if ($hasActiveInvoice) {
                    throw new DomainException('Termin sudah berada pada invoice aktif.');
                }

                $grandTotal = bcadd($grandTotal, (string) $term->amount, 6);
            }

            $amounts = $this->calculateAmounts($order, $grandTotal, $termIds);

            $invoice = TransSalesInvoiceHd::query()->create([
                'code' => $this->generateCode->next(AutoCodePrefix::TransSalesInvoiceHd),
                'transaction_date' => $data['transaction_date'],
                'due_date' => $data['due_date'],
                'order_hd_id' => $order->id,
                'project_hd_id' => $order->project()->value('id'),
                'quotation_hd_id' => $order->quotation_hd_id,
                'client_id' => $order->client_id,
                'company_id' => $order->company_id,
                'bank_account_id' => $data['bank_account_id'],
                'subtotal' => $amounts['subtotal'],
                'vat_type' => $order->vat_type,
                'vat_amount' => $amounts['vat_amount'],
                'grand_total' => $amounts['grand_total'],
                'notes' => $data['notes'] ?? null,
                'status_id' => MsStatus::query()
                    ->where('type', InvoiceStatus::type()->value)
                    ->where('code', InvoiceStatus::Unpaid->value)
                    ->where('active', true)
                    ->firstOrFail()->id,
            ]);

            $invoicedStatusId = MsStatus::query()
                ->where('type', TermStatus::type()->value)
                ->where('code', TermStatus::Invoiced->value)
                ->where('active', true)
                ->firstOrFail()->id;

            foreach ($terms->sortBy('sequence') as $term) {
                $invoice->items()->create([
                    'sales_term_id' => $term->id,
                    'sequence' => $term->sequence,
                    'description' => $term->term_name,
                    'percentage' => $term->percentage,
                    'amount' => $term->amount,
                ]);
                $term->update(['status_id' => $invoicedStatusId]);
            }

            if ($invoice->project_hd_id !== null) {
                $order->project?->stages()
                    ->whereHas('status', fn (Builder $query): Builder => $query
                        ->where('type', ProjectStageStatus::type()->value)
                        ->where('code', ProjectStageStatus::Completed->value))
                    ->update(['editable' => false]);
            }

            return $invoice;
        });
    }

    /**
     * @param  array<int, int>  $termIds
     * @return array{subtotal: string, vat_amount: string, grand_total: string}
     */
    private function calculateAmounts(TransSalesOrderHd $order, string $grandTotal, array $termIds): array
    {
        if (bccomp($grandTotal, '0', 6) === 0) {
            return ['subtotal' => '0.000000', 'vat_amount' => '0.000000', 'grand_total' => '0.000000'];
        }

        $activeInvoices = $order->invoices()->with(['status', 'items'])->get()
            ->filter(fn (TransSalesInvoiceHd $invoice): bool => $invoice->status?->code !== InvoiceStatus::Void->value);
        $priorVat = $activeInvoices->reduce(
            fn (string $carry, TransSalesInvoiceHd $invoice): string => bcadd($carry, (string) $invoice->vat_amount, 6),
            '0.000000',
        );
        $invoicedTermIds = $activeInvoices->flatMap(fn (TransSalesInvoiceHd $invoice) => $invoice->items->pluck('sales_term_id'));
        $isLastInvoice = $order->terms()->count() === $invoicedTermIds->merge($termIds)->unique()->count();
        $remainingVat = bcsub((string) $order->vat_amount, $priorVat, 6);
        $vatAmount = $order->vat_type === VatType::None || bccomp((string) $order->grand_total, '0', 6) === 0
            ? '0.000000'
            : ($isLastInvoice
                ? $remainingVat
                : bcmul((string) $order->vat_amount, bcdiv($grandTotal, (string) $order->grand_total, 12), 6));

        return [
            'subtotal' => $order->vat_type === VatType::None
                ? $grandTotal
                : bcsub($grandTotal, $vatAmount, 6),
            'vat_amount' => $vatAmount,
            'grand_total' => $grandTotal,
        ];
    }
}
