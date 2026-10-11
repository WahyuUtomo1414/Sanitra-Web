<?php

namespace App\Services\Quotation;

use App\Enums\Status\QuotationStatus;
use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use DomainException;
use Illuminate\Support\Facades\DB;

class RejectQuotationService
{
    public function execute(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        return DB::transaction(function () use ($quotation): TransSalesQuotationHd {
            $quotation = TransSalesQuotationHd::query()->lockForUpdate()->findOrFail($quotation->id);

            if ($quotation->status?->code !== QuotationStatus::Sent->value) {
                throw new DomainException('Hanya quotation Terkirim yang dapat ditolak.');
            }

            $quotation->update([
                'status_id' => MsStatus::query()->where('type', QuotationStatus::type()->value)
                    ->where('code', QuotationStatus::Rejected->value)->where('active', true)->firstOrFail()->id,
            ]);

            return $quotation->refresh();
        });
    }
}
