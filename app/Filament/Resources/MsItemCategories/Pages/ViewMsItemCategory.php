<?php

namespace App\Filament\Resources\MsItemCategories\Pages;

use App\Filament\Resources\MsItemCategories\MsItemCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsItemCategory extends ViewRecord
{
    protected static string $resource = MsItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
