<?php

namespace App\Filament\Resources\MsStatuses\Pages;

use App\Filament\Resources\MsStatuses\MsStatusResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsStatus extends ViewRecord
{
    protected static string $resource = MsStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
