<?php

namespace App\Filament\Resources\MsCompanies\Pages;

use App\Filament\Resources\MsCompanies\MsCompanyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsCompany extends ViewRecord
{
    protected static string $resource = MsCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
