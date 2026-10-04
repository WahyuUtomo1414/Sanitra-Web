<?php

namespace App\Filament\Resources\MsUnits\Pages;

use App\Filament\Resources\MsUnits\MsUnitResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsUnit extends ViewRecord
{
    protected static string $resource = MsUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
