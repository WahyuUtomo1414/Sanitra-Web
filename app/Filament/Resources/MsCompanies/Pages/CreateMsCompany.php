<?php

namespace App\Filament\Resources\MsCompanies\Pages;

use App\Enums\AutoCodePrefix;
use App\Filament\Resources\MsCompanies\MsCompanyResource;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMsCompany extends CreateRecord
{
    protected static string $resource = MsCompanyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsCompany);
        $data['parent_id'] = 1;

        return $data;
    }
}
