<?php

namespace App\Services\Quotation;

use App\Enums\Status\QuotationStatus;
use App\Models\MsStatus;
use App\Models\TransSalesQuotationHd;
use App\Services\SalesOrder\CreateSalesOrderFromQuotationService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApproveQuotationService
{
    public function __construct(public CreateSalesOrderFromQuotationService $createSalesOrder) {}

    public function execute(TransSalesQuotationHd $quotation): TransSalesQuotationHd
    {
        return DB::transaction(function () use ($quotation): TransSalesQuotationHd {
            $quotation = TransSalesQuotationHd::query()->lockForUpdate()->findOrFail($quotation->id);

            if ($quotation->status?->code !== QuotationStatus::Sent->value) {
                throw new DomainException('Hanya quotation Terkirim yang dapat disetujui.');
            }

            // Panggil pembuatan Sales Order sebelum flip status ke `approved` karena
            // CreateSalesOrderFromQuotationService memakai status `sent` sebagai guard.
            $this->createSalesOrder->execute($quotation);

            $quotation->update([
                'status_id' => MsStatus::query()->where('type', QuotationStatus::type()->value)
                    ->where('code', QuotationStatus::Approved->value)->where('active', true)->firstOrFail()->id,
            ]);

            return $quotation->refresh();
        });
    }
}
