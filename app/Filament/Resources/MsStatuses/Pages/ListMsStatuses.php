<?php

namespace App\Filament\Resources\MsStatuses\Pages;

use App\Filament\Resources\MsStatuses\MsStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsStatuses extends ListRecords
{
    protected static string $resource = MsStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
