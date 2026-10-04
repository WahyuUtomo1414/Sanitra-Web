<?php

namespace App\Filament\Resources\MsItems\Pages;

use App\Filament\Resources\MsItems\MsItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsItems extends ListRecords
{
    protected static string $resource = MsItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
