<?php

namespace App\Services\Quotation;

use App\Enums\Status\QuotationStatus;
use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReviseQuotationService
{
    public function execute(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        return DB::transaction(function () use ($quotation): TransSalesQuotationHd {
            $quotation = TransSalesQuotationHd::query()->lockForUpdate()->findOrFail($quotation->id);

            if ($quotation->status?->code !== QuotationStatus::Sent->value) {
                throw new DomainException('Hanya quotation Terkirim yang dapat direvisi.');
            }

            $quotation->update([
                'status_id' => MsStatus::query()->where('type', QuotationStatus::type()->value)
                    ->where('code', QuotationStatus::Draft->value)->where('active', true)->firstOrFail()->id,
            ]);

            return $quotation->refresh();
        });
    }
}
