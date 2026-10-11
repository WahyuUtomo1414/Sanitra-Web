<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Pages;

use App\Enums\Status\QuotationStatus;
use App\Filament\Resources\TransSalesQuotationHds\TransSalesQuotationHdResource;
use App\Services\Quotation\RecalculateQuotationTotalsService;
use DomainException;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTransSalesQuotationHd extends EditRecord
{
    protected static string $resource = TransSalesQuotationHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());

            if ($record->status?->code !== QuotationStatus::Draft->value) {
                throw new DomainException('Quotation hanya dapat diedit saat Draft.');
            }

            $record->update($data);

            return app(RecalculateQuotationTotalsService::class)->execute($record);
        });
    }
}
