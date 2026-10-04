<?php

namespace App\Filament\Resources\MsProjectTypes\Pages;

use App\Enums\AutoCodePrefix;
use App\Filament\Resources\MsProjectTypes\MsProjectTypeResource;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMsProjectType extends CreateRecord
{
    protected static string $resource = MsProjectTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsProjectType);

        return $data;
    }
}
