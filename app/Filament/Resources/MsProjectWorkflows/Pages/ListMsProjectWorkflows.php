<?php

namespace App\Filament\Resources\MsProjectWorkflows\Pages;

use App\Filament\Resources\MsProjectWorkflows\MsProjectWorkflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMsProjectWorkflows extends ListRecords
{
    protected static string $resource = MsProjectWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
