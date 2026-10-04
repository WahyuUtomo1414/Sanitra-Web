<?php

namespace App\Filament\Resources\MsBankAccounts\Pages;

use App\Filament\Resources\MsBankAccounts\MsBankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsBankAccounts extends ListRecords
{
    protected static string $resource = MsBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
