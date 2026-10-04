<?php

namespace App\Filament\Resources\MsProjectWorkflows;

use App\Filament\Resources\MsProjectWorkflows\Pages\CreateMsProjectWorkflow;
use App\Filament\Resources\MsProjectWorkflows\Pages\EditMsProjectWorkflow;
use App\Filament\Resources\MsProjectWorkflows\Pages\ListMsProjectWorkflows;
use App\Filament\Resources\MsProjectWorkflows\Pages\ViewMsProjectWorkflow;
use App\Filament\Resources\MsProjectWorkflows\Schemas\MsProjectWorkflowForm;
use App\Filament\Resources\MsProjectWorkflows\Schemas\MsProjectWorkflowInfolist;
use App\Filament\Resources\MsProjectWorkflows\Tables\MsProjectWorkflowsTable;
use App\Models\MsProjectWorkflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsProjectWorkflowResource extends Resource
{
    protected static ?string $model = MsProjectWorkflow::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'Project';

    protected static ?string $navigationLabel = 'Workflow Project';

    protected static ?string $modelLabel = 'Workflow Project';

    protected static ?string $pluralModelLabel = 'Workflow Project';

    protected static ?string $slug = 'project-workflows';

    public static function form(Schema $schema): Schema
    {
        return MsProjectWorkflowForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsProjectWorkflowInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsProjectWorkflowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMsProjectWorkflows::route('/'),
            'create' => CreateMsProjectWorkflow::route('/create'),
            'view' => ViewMsProjectWorkflow::route('/{record}'),
            'edit' => EditMsProjectWorkflow::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
