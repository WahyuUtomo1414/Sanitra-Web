<?php

namespace App\Filament\Resources\MsEmployees\Pages;

use App\Filament\Resources\MsEmployees\MsEmployeeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsEmployee extends ViewRecord
{
    protected static string $resource = MsEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
