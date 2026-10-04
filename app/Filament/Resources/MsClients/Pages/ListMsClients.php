<?php

namespace App\Filament\Resources\MsClients\Pages;

use App\Filament\Resources\MsClients\MsClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsClients extends ListRecords
{
    protected static string $resource = MsClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
