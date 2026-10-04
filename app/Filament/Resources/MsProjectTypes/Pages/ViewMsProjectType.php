<?php

namespace App\Filament\Resources\MsProjectTypes\Pages;

use App\Filament\Resources\MsProjectTypes\MsProjectTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsProjectType extends ViewRecord
{
    protected static string $resource = MsProjectTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
