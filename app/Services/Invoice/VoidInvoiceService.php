<?php

namespace App\Services\Invoice;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Enums\Status\TermStatus;
use App\Models\MsStatus;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesOrderHd;
use App\Services\SalesOrder\RecalculateOrderPaymentStatusService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VoidInvoiceService
{
    public function __construct(public RecalculateOrderPaymentStatusService $recalculateOrderPaymentStatus) {}

    public function execute(TransSalesInvoiceHd $invoice, string $reason): TransSalesInvoiceHd
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new DomainException('Alasan pembatalan wajib diisi.');
        }

        return DB::transaction(function () use ($invoice, $reason): TransSalesInvoiceHd {
            $invoiceReference = TransSalesInvoiceHd::query()->findOrFail($invoice->id);
            TransSalesOrderHd::query()->lockForUpdate()->findOrFail($invoiceReference->order_hd_id);
            $invoice = TransSalesInvoiceHd::query()->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->status?->code !== InvoiceStatus::Unpaid->value) {
                throw new DomainException('Hanya invoice Belum Dibayar yang dapat dibatalkan.');
            }

            if ($invoice->project_hd_id !== null) {
                throw new DomainException('Pembatalan invoice project belum tersedia pada fase ini.');
            }

            $hasActivePayment = $invoice->payments()
                ->whereHas('status', fn (Builder $query): Builder => $query
                    ->where('type', PaymentStatus::type()->value)
                    ->whereIn('code', [PaymentStatus::Pending->value, PaymentStatus::Confirmed->value]))
                ->exists();

            if ($hasActivePayment) {
                throw new DomainException('Tolak pembayaran yang menunggu verifikasi sebelum membatalkan invoice.');
            }

            $invoice->update([
                'status_id' => MsStatus::query()
                    ->where('type', InvoiceStatus::type()->value)
                    ->where('code', InvoiceStatus::Void->value)
                    ->where('active', true)
                    ->firstOrFail()->id,
                'notes' => trim(($invoice->notes ? $invoice->notes."\n\n" : '').'Alasan pembatalan: '.$reason),
            ]);

            $readyTermStatusId = MsStatus::query()
                ->where('type', TermStatus::type()->value)
                ->where('code', TermStatus::ReadyToBill->value)
                ->where('active', true)
                ->firstOrFail()->id;
            $invoice->order->terms()->whereKey($invoice->items()->pluck('sales_term_id'))
                ->update(['status_id' => $readyTermStatusId]);

            $this->recalculateOrderPaymentStatus->execute($invoice->order);

            return $invoice->refresh();
        });
    }
}
