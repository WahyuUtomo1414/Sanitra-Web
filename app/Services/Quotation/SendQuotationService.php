<?php

namespace App\Services\Quotation;

use App\Enums\Status\QuotationStatus;
use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use DomainException;
use Illuminate\Support\Facades\DB;

class SendQuotationService
{
    public function __construct(
        public RecalculateQuotationService $recalculate,
        public QuotationReadinessService $readiness,
    ) {}

    public function execute(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        return DB::transaction(function () use ($quotation): TransSalesQuotationHd {
            $quotation = TransSalesQuotationHd::query()->lockForUpdate()->findOrFail($quotation->id);

            if ($quotation->status?->code !== QuotationStatus::Draft->value) {
                throw new DomainException('Hanya quotation Draft yang dapat dikirim.');
            }

            $this->readiness->ensureReady($quotation);

            $this->recalculate->totals($quotation);

            $quotation->update([
                'status_id' => MsStatus::query()->where('type', QuotationStatus::type()->value)
                    ->where('code', QuotationStatus::Sent->value)->where('active', true)->firstOrFail()->id,
            ]);

            return $quotation->refresh();
        });
    }
}
