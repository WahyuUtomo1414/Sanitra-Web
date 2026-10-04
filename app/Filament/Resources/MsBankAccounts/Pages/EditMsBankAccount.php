<?php

namespace App\Filament\Resources\MsBankAccounts\Pages;

use App\Filament\Resources\MsBankAccounts\MsBankAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMsBankAccount extends EditRecord
{
    protected static string $resource = MsBankAccountResource::class;

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
