<?php

namespace App\Filament\Resources\MsCompanies\Pages;

use App\Filament\Resources\MsCompanies\MsCompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMsCompany extends EditRecord
{
    protected static string $resource = MsCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn () => $this->record->getKey() !== 1),
            ForceDeleteAction::make()->visible(fn () => $this->record->getKey() !== 1),
            RestoreAction::make(),
        ];
    }
}
