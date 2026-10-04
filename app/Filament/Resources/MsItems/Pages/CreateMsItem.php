<?php

namespace App\Filament\Resources\MsItems\Pages;

use App\Enums\AutoCodePrefix;
use App\Filament\Resources\MsItems\MsItemResource;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMsItem extends CreateRecord
{
    protected static string $resource = MsItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsItem);

        return $data;
    }
}
