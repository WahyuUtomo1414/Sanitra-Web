<?php

namespace App\Filament\Resources\MsProjectWorkflows\Pages;

use App\Filament\Resources\MsProjectWorkflows\MsProjectWorkflowResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMsProjectWorkflow extends ViewRecord
{
    protected static string $resource = MsProjectWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
