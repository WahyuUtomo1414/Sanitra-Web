<?php

namespace App\Services\Payment;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\OrderStatus;
use App\Enums\Status\PaymentStatus;
use App\Enums\Status\TermStatus;
use App\Models\MsStatus;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesPayment;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ConfirmPaymentService
{
    public function execute(TransSalesPayment $payment): TransSalesPayment
    {
        return DB::transaction(function () use ($payment): TransSalesPayment {
            $paymentReference = TransSalesPayment::query()->findOrFail($payment->id);
            $invoiceReference = TransSalesInvoiceHd::query()->findOrFail($paymentReference->invoice_hd_id);
            $order = TransSalesOrderHd::query()->with('status')->lockForUpdate()->findOrFail($invoiceReference->order_hd_id);
            $invoice = TransSalesInvoiceHd::query()->lockForUpdate()->findOrFail($invoiceReference->id);
            $payment = TransSalesPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->invoice_hd_id !== $invoice->id
                || $payment->status?->code !== PaymentStatus::Pending->value
                || $invoice->status?->code !== InvoiceStatus::Unpaid->value) {
                throw new DomainException('Pembayaran tidak lagi dapat dikonfirmasi.');
            }

            if (bccomp((string) $payment->amount, (string) $invoice->grand_total, 6) !== 0) {
                throw new DomainException('Nominal pembayaran tidak sama dengan total invoice.');
            }

            $hasConfirmedPayment = $invoice->payments()
                ->whereKeyNot($payment->id)
                ->whereHas('status', fn (Builder $query): Builder => $query
                    ->where('type', PaymentStatus::type()->value)
                    ->where('code', PaymentStatus::Confirmed->value))
                ->exists();

            if ($hasConfirmedPayment) {
                throw new DomainException('Invoice sudah memiliki pembayaran yang dikonfirmasi.');
            }

            $termIds = $invoice->items()->pluck('sales_term_id')->all();
            $terms = $order->terms()->whereKey($termIds)->with('status')->lockForUpdate()->get();

            if ($termIds === [] || $terms->count() !== count($termIds)
                || $terms->contains(fn ($term): bool => $term->status?->code !== TermStatus::Invoiced->value)) {
                throw new DomainException('Termin invoice tidak valid untuk dilunasi.');
            }

            $payment->update(['status_id' => $this->statusId(PaymentStatus::type()->value, PaymentStatus::Confirmed->value)]);
            $invoice->update(['status_id' => $this->statusId(InvoiceStatus::type()->value, InvoiceStatus::Paid->value)]);

            $paidTermStatusId = $this->statusId(TermStatus::type()->value, TermStatus::Paid->value);

            foreach ($terms as $term) {
                $term->update(['status_id' => $paidTermStatusId]);
            }

            $hasDpTerm = $terms->contains(fn ($term): bool => (int) $term->progress_threshold === 0);
            if ($hasDpTerm && $order->status?->code === OrderStatus::Draft->value) {
                $order->update(['status_id' => $this->statusId(OrderStatus::type()->value, OrderStatus::Open->value)]);
            }

            return $payment->refresh();
        });
    }

    private function statusId(string $type, string $code): int
    {
        return MsStatus::query()->where('type', $type)->where('code', $code)->where('active', true)->firstOrFail()->id;
    }
}
