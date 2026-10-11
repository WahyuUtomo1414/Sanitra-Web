<?php

namespace App\Services\SalesOrder;

use App\Enums\AutoCodePrefix;
use App\Enums\Status\OrderPaymentStatus;
use App\Enums\Status\OrderStatus;
use App\Enums\Status\QuotationStatus;
use App\Enums\Status\TermStatus;
use App\Models\MsStatus;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesQuotationHd;
use App\Services\Shared\GenerateCodeService;
use DomainException;

class CreateSalesOrderFromQuotationService
{
    public function __construct(public GenerateCodeService $generateCode) {}

    public function execute(TransSalesQuotationHd $quotation): TransSalesOrderHd
    {
        if ($quotation->status?->code !== QuotationStatus::Sent->value) {
            throw new DomainException('Sales Order hanya dapat dibuat dari quotation Terkirim.');
        }

        if ($quotation->order()->exists()) {
            throw new DomainException('Quotation ini sudah memiliki Sales Order.');
        }

        $quotation->load(['items', 'terms']);

        if ($quotation->items->isEmpty() || $quotation->terms->isEmpty()) {
            throw new DomainException('Item dan termin quotation wajib tersedia.');
        }

        if ($quotation->terms->sum('percentage') !== 100
            || $quotation->terms->where('progress_threshold', 0)->count() !== 1) {
            throw new DomainException('Skema termin quotation harus berjumlah 100% dengan tepat satu DP.');
        }

        $order = TransSalesOrderHd::query()->create([
            'code' => $this->generateCode->next(AutoCodePrefix::TransSalesOrderHd),
            'transaction_date' => now()->toDateString(),
            'quotation_hd_id' => $quotation->id,
            'client_id' => $quotation->client_id,
            'project_type_id' => $quotation->project_type_id,
            'company_id' => $quotation->company_id,
            'payment_type' => $quotation->payment_type,
            'vat_type' => $quotation->vat_type,
            'vat_amount' => $quotation->vat_amount,
            'discount_total' => $quotation->discount_total,
            'subtotal' => $quotation->subtotal,
            'grand_total' => $quotation->grand_total,
            'notes' => $quotation->notes,
            'status_id' => $this->statusId(OrderStatus::type()->value, OrderStatus::WaitingDp->value),
            'payment_status_id' => $this->statusId(OrderPaymentStatus::type()->value, OrderPaymentStatus::Unpaid->value),
        ]);

        foreach ($quotation->items as $item) {
            $order->items()->create([
                'quotation_dt_id' => $item->id,
                'item_id' => $item->item_id,
                'unit_id' => $item->unit_id,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'discount' => $item->discount,
                'total_price' => $item->total_price,
                'notes' => $item->notes,
            ]);
        }

        $allocated = '0.000000';
        $lastTermId = $quotation->terms->sortBy('sequence')->last()->id;

        foreach ($quotation->terms->sortBy('sequence') as $term) {
            $amount = $term->id === $lastTermId
                ? bcsub((string) $quotation->grand_total, $allocated, 6)
                : bcdiv(bcmul((string) $quotation->grand_total, (string) $term->percentage, 6), '100', 6);
            $allocated = bcadd($allocated, $amount, 6);

            $term->update([
                'order_hd_id' => $order->id,
                'amount' => $amount,
                'status_id' => $this->statusId(
                    TermStatus::type()->value,
                    (int) $term->progress_threshold === 0 ? TermStatus::ReadyToBill->value : TermStatus::Pending->value,
                ),
            ]);
        }

        return $order;
    }

    private function statusId(string $type, string $code): int
    {
        return MsStatus::query()->where('type', $type)->where('code', $code)->where('active', true)->firstOrFail()->id;
    }
}
