<?php

namespace App\Filament\Resources\TransSalesOrderHds\Pages;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTransSalesOrderHd extends EditRecord
{
    protected static string $resource = TransSalesOrderHdResource::class;

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
            $record->update(['notes' => $data['notes'] ?? null]);

            return $record;
        });
    }
}
