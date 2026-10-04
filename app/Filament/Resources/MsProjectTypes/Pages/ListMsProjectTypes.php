<?php

namespace App\Filament\Resources\MsProjectTypes\Pages;

use App\Filament\Resources\MsProjectTypes\MsProjectTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsProjectTypes extends ListRecords
{
    protected static string $resource = MsProjectTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
