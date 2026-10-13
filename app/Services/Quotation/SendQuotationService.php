<?php

namespace App\Services\Quotation;

use App\Enums\Status\QuotationStatus;
use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use DomainException;
use Illuminate\Support\Facades\DB;

class SendQuotationService
{
    public function __construct(public RecalculateQuotationService $recalculate) {}

    public function execute(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        return DB::transaction(function () use ($quotation): TransSalesQuotationHd {
            $quotation = TransSalesQuotationHd::query()->lockForUpdate()->findOrFail($quotation->id);

            if ($quotation->status?->code !== QuotationStatus::Draft->value) {
                throw new DomainException('Hanya quotation Draft yang dapat dikirim.');
            }

            if (! $quotation->items()->exists()) {
                throw new DomainException('Quotation harus memiliki minimal satu item.');
            }

            $terms = $quotation->terms()->get();

            if ($terms->isEmpty() || $terms->sum('percentage') !== 100) {
                throw new DomainException('Total persentase termin harus 100%.');
            }

            if ($terms->where('progress_threshold', 0)->count() !== 1) {
                throw new DomainException('Quotation harus memiliki tepat satu termin DP dengan threshold 0%.');
            }

            $this->recalculate->totals($quotation);

            $quotation->update([
                'status_id' => MsStatus::query()->where('type', QuotationStatus::type()->value)
                    ->where('code', QuotationStatus::Sent->value)->where('active', true)->firstOrFail()->id,
            ]);

            return $quotation->refresh();
        });
    }
}
