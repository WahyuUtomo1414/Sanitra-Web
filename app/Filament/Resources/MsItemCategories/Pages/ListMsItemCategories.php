<?php

namespace App\Filament\Resources\MsItemCategories\Pages;

use App\Filament\Resources\MsItemCategories\MsItemCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsItemCategories extends ListRecords
{
    protected static string $resource = MsItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
