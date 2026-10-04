<?php

namespace App\Filament\Resources\MsClients\Pages;

use App\Filament\Resources\MsClients\MsClientResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsClient extends ViewRecord
{
    protected static string $resource = MsClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
