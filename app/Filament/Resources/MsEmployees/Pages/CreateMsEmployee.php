<?php

namespace App\Filament\Resources\MsEmployees\Pages;

use App\Enums\AutoCodePrefix;
use App\Filament\Resources\MsEmployees\MsEmployeeResource;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMsEmployee extends CreateRecord
{
    protected static string $resource = MsEmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsEmployee);

        return $data;
    }
}
