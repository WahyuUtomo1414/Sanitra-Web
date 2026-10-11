<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Pages;

use App\Enums\AutoCodePrefix;
use App\Enums\Status\QuotationStatus;
use App\Filament\Resources\TransSalesQuotationHds\TransSalesQuotationHdResource;
use App\Models\MsStatus;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateTransSalesQuotationHd extends CreateRecord
{
    protected static string $resource = TransSalesQuotationHdResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::TransSalesQuotationHd);
        $data['status_id'] = MsStatus::query()->where('type', QuotationStatus::type()->value)
            ->where('code', QuotationStatus::Draft->value)->where('active', true)->firstOrFail()->id;
        $data['vat_amount'] = 0;
        $data['discount_total'] = 0;
        $data['subtotal'] = 0;
        $data['grand_total'] = 0;

        return $data;
    }
}
