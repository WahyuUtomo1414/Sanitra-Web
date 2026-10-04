<?php

namespace App\Filament\Resources\MsEmployees\Pages;

use App\Filament\Resources\MsEmployees\MsEmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsEmployees extends ListRecords
{
    protected static string $resource = MsEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
