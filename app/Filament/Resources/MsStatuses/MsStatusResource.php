<?php

namespace App\Filament\Resources\MsStatuses;

use App\Filament\Resources\MsStatuses\Pages\CreateMsStatus;
use App\Filament\Resources\MsStatuses\Pages\EditMsStatus;
use App\Filament\Resources\MsStatuses\Pages\ListMsStatuses;
use App\Filament\Resources\MsStatuses\Pages\ViewMsStatus;
use App\Filament\Resources\MsStatuses\Schemas\MsStatusForm;
use App\Filament\Resources\MsStatuses\Schemas\MsStatusInfolist;
use App\Filament\Resources\MsStatuses\Tables\MsStatusesTable;
use App\Models\MsStatus;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsStatusResource extends Resource
{
    protected static ?string $model = MsStatus::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Status';

    protected static ?string $modelLabel = 'Status';

    protected static ?string $pluralModelLabel = 'Status';

    protected static ?string $slug = 'statuses';

    public static function form(Schema $schema): Schema
    {
        return MsStatusForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsStatusInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsStatusesTable::configure($table);
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
            'index' => ListMsStatuses::route('/'),
            'create' => CreateMsStatus::route('/create'),
            'view' => ViewMsStatus::route('/{record}'),
            'edit' => EditMsStatus::route('/{record}/edit'),
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
