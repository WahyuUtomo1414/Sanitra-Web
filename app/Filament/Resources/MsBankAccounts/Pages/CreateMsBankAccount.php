<?php

namespace App\Filament\Resources\MsBankAccounts\Pages;

use App\Enums\AutoCodePrefix;
use App\Filament\Resources\MsBankAccounts\MsBankAccountResource;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMsBankAccount extends CreateRecord
{
    protected static string $resource = MsBankAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsBankAccount);

        return $data;
    }
}
