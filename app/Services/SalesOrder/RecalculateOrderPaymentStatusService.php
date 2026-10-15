<?php

namespace App\Services\SalesOrder;

use App\Enums\Status\OrderPaymentStatus;
use App\Enums\Status\TermStatus;
use App\Models\MsStatus;
use App\Models\TransSalesOrderHd;
use Illuminate\Support\Facades\DB;

class RecalculateOrderPaymentStatusService
{
    public function execute(TransSalesOrderHd $order): TransSalesOrderHd
    {
        return DB::transaction(function () use ($order): TransSalesOrderHd {
            $order = TransSalesOrderHd::query()->lockForUpdate()->findOrFail($order->id);
            $terms = $order->terms()->with('status')->get();
            $paidCount = $terms->filter(fn ($term): bool => $term->status?->code === TermStatus::Paid->value)->count();

            $paymentStatus = match (true) {
                $paidCount === 0 => OrderPaymentStatus::Unpaid,
                $paidCount === $terms->count() => OrderPaymentStatus::Paid,
                default => OrderPaymentStatus::Partial,
            };

            $statusId = MsStatus::query()
                ->where('type', OrderPaymentStatus::type()->value)
                ->where('code', $paymentStatus->value)
                ->where('active', true)
                ->firstOrFail()->id;

            if ($order->payment_status_id !== $statusId) {
                $order->update(['payment_status_id' => $statusId]);
            }

            return $order->refresh();
        });
    }
}
