<?php

namespace App\Filament\Resources\MsItemCategories\Pages;

use App\Filament\Resources\MsItemCategories\MsItemCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMsItemCategory extends EditRecord
{
    protected static string $resource = MsItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
