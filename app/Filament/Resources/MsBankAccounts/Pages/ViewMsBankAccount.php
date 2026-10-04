<?php

namespace App\Filament\Resources\MsBankAccounts\Pages;

use App\Filament\Resources\MsBankAccounts\MsBankAccountResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsBankAccount extends ViewRecord
{
    protected static string $resource = MsBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
