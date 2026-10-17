<?php

namespace App\Services\SalesOrder;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\OrderCloseReason;
use App\Enums\Status\OrderStatus;
use App\Enums\Status\ProjectStatus;
use App\Models\MsStatus;
use App\Models\TransSalesOrderHd;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CloseOrderService
{
    public function execute(TransSalesOrderHd $order, string $closeReason, ?string $closeNotes = null): TransSalesOrderHd
    {
        return DB::transaction(function () use ($order, $closeReason, $closeNotes): TransSalesOrderHd {
            $order = TransSalesOrderHd::query()->with('status')->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status?->code, [OrderStatus::Draft->value, OrderStatus::Open->value])) {
                throw new DomainException('Sales Order hanya dapat ditutup saat berstatus Menunggu DP atau Berjalan.');
            }

            $hasUnpaidInvoice = $order->invoices()
                ->whereHas('status', fn (Builder $query): Builder => $query
                    ->where('type', InvoiceStatus::type()->value)
                    ->where('code', InvoiceStatus::Unpaid->value))
                ->exists();

            if ($hasUnpaidInvoice) {
                throw new DomainException('Selesaikan semua invoice yang belum dibayar sebelum menutup Sales Order.');
            }

            $closeReasonEnum = OrderCloseReason::tryFrom($closeReason);
            if ($closeReasonEnum === null) {
                throw new DomainException('Alasan penutupan tidak valid.');
            }

            $project = $order->project()->with('status')->first();
            if ($project !== null && $project->status?->code === ProjectStatus::OnProgress->value) {
                $project->update([
                    'status_id' => MsStatus::query()
                        ->where('type', ProjectStatus::type()->value)
                        ->where('code', ProjectStatus::Cancelled->value)
                        ->where('active', true)
                        ->firstOrFail()->id,
                ]);
            }

            $order->update([
                'status_id' => MsStatus::query()
                    ->where('type', OrderStatus::type()->value)
                    ->where('code', OrderStatus::Closed->value)
                    ->where('active', true)
                    ->firstOrFail()->id,
                'close_reason' => $closeReasonEnum->value,
                'close_notes' => $closeNotes !== null ? trim($closeNotes) : null,
            ]);

            return $order->refresh();
        });
    }
}
