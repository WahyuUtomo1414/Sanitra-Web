<?php

namespace App\Filament\Resources\MsCompanies\Pages;

use App\Filament\Resources\MsCompanies\MsCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsCompanies extends ListRecords
{
    protected static string $resource = MsCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
