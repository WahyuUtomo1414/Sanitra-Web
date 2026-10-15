<?php

namespace App\Services\Payment;

use App\Enums\AutoCodePrefix;
use App\Enums\PaymentMethod;
use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Models\MsStatus;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesPayment;
use App\Services\Shared\GenerateCodeService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecordPaymentService
{
    public function __construct(public GenerateCodeService $generateCode) {}

    /** @param array{transaction_date: string, amount: int|float|string, payment_method: string, reference_no?: string|null, payment_proof?: string|null, notes?: string|null} $data */
    public function execute(TransSalesInvoiceHd $invoice, array $data): TransSalesPayment
    {
        $data = Validator::make($data, [
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_no' => ['nullable', 'string', 'max:50'],
            'payment_proof' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($invoice, $data): TransSalesPayment {
            $invoice = TransSalesInvoiceHd::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->status?->code !== InvoiceStatus::Unpaid->value) {
                throw new DomainException('Pembayaran hanya dapat dicatat untuk invoice Belum Dibayar.');
            }

            if (preg_match('/^\d{1,14}(?:\.\d{1,6})?$/D', (string) $data['amount']) !== 1) {
                throw new DomainException('Nominal pembayaran harus menggunakan maksimal 14 digit dan 6 angka desimal.');
            }

            if (bccomp((string) $data['amount'], (string) $invoice->grand_total, 6) !== 0) {
                throw new DomainException('Nominal pembayaran harus sama dengan total invoice.');
            }

            $hasActivePayment = $invoice->payments()
                ->whereHas('status', fn (Builder $query): Builder => $query
                    ->where('type', PaymentStatus::type()->value)
                    ->whereIn('code', [PaymentStatus::Pending->value, PaymentStatus::Confirmed->value]))
                ->exists();

            if ($hasActivePayment) {
                throw new DomainException('Invoice sudah memiliki pembayaran yang menunggu verifikasi atau telah dikonfirmasi.');
            }

            $proofPath = $data['payment_proof'] ?? null;

            if ($proofPath !== null && (! str_starts_with($proofPath, 'payments/proof/') || ! Storage::disk('public')->exists($proofPath))) {
                throw new DomainException('Bukti pembayaran tidak valid.');
            }

            return $invoice->payments()->create([
                'code' => $this->generateCode->next(AutoCodePrefix::TransSalesPayment),
                'transaction_date' => $data['transaction_date'],
                'amount' => $invoice->grand_total,
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['reference_no'] ?? null,
                'payment_proof' => $proofPath,
                'notes' => $data['notes'] ?? null,
                'status_id' => MsStatus::query()
                    ->where('type', PaymentStatus::type()->value)
                    ->where('code', PaymentStatus::Pending->value)
                    ->where('active', true)
                    ->firstOrFail()->id,
            ]);
        });
    }
}
