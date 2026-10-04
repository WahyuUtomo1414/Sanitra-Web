<?php

namespace App\Filament\Resources\MsClients\Pages;

use App\Enums\AutoCodePrefix;
use App\Filament\Resources\MsClients\MsClientResource;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMsClient extends CreateRecord
{
    protected static string $resource = MsClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsClient);

        return $data;
    }
}
