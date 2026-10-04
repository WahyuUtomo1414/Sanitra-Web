<?php

namespace App\Filament\Resources\MsItems\Pages;

use App\Filament\Resources\MsItems\MsItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsItem extends ViewRecord
{
    protected static string $resource = MsItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
