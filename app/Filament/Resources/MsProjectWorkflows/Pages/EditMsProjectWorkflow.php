<?php

namespace App\Filament\Resources\MsProjectWorkflows\Pages;

use App\Filament\Resources\MsProjectWorkflows\MsProjectWorkflowResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMsProjectWorkflow extends EditRecord
{
    protected static string $resource = MsProjectWorkflowResource::class;

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
