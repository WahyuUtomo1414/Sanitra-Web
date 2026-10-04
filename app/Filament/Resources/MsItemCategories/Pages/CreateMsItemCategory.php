<?php

namespace App\Filament\Resources\MsItemCategories\Pages;

use App\Enums\AutoCodePrefix;
use App\Filament\Resources\MsItemCategories\MsItemCategoryResource;
use App\Services\Shared\GenerateCodeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMsItemCategory extends CreateRecord
{
    protected static string $resource = MsItemCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsItemCategory);

        return $data;
    }
}
