<?php

namespace App\Filament\Resources\MsUnits\Pages;

use App\Filament\Resources\MsUnits\MsUnitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsUnits extends ListRecords
{
    protected static string $resource = MsUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
