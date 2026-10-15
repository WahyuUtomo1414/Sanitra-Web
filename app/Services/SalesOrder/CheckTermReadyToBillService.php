<?php

namespace App\Services\SalesOrder;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\TermStatus;
use App\Models\MsStatus;
use App\Models\TransSalesOrderHd;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CheckTermReadyToBillService
{
    public function execute(TransSalesOrderHd $order, int $progressPercentage): int
    {
        if ($progressPercentage < 0 || $progressPercentage > 100) {
            throw new DomainException('Progress proyek harus berada di antara 0 dan 100%.');
        }

        return DB::transaction(function () use ($order, $progressPercentage): int {
            $readyStatusId = MsStatus::query()
                ->where('type', TermStatus::type()->value)
                ->where('code', TermStatus::ReadyToBill->value)
                ->where('active', true)
                ->firstOrFail()->id;

            $pendingTerms = $order->terms()
                ->whereHas('status', fn (Builder $query): Builder => $query
                    ->where('type', TermStatus::type()->value)
                    ->where('code', TermStatus::Pending->value))
                ->where('progress_threshold', '<=', $progressPercentage)
                ->lockForUpdate()
                ->get();

            $updated = 0;

            foreach ($pendingTerms as $term) {
                $hasActiveInvoice = $term->invoiceDetails()
                    ->whereHas('invoice.status', fn (Builder $query): Builder => $query
                        ->where('type', InvoiceStatus::type()->value)
                        ->where('code', '!=', InvoiceStatus::Void->value))
                    ->exists();

                if ($hasActiveInvoice) {
                    continue;
                }

                $term->update(['status_id' => $readyStatusId]);
                $updated++;
            }

            return $updated;
        });
    }
}
