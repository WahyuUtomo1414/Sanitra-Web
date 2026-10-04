<?php

namespace App\Filament\Resources\MsUnits;

use App\Filament\Resources\MsUnits\Pages\CreateMsUnit;
use App\Filament\Resources\MsUnits\Pages\EditMsUnit;
use App\Filament\Resources\MsUnits\Pages\ListMsUnits;
use App\Filament\Resources\MsUnits\Pages\ViewMsUnit;
use App\Filament\Resources\MsUnits\Schemas\MsUnitForm;
use App\Filament\Resources\MsUnits\Schemas\MsUnitInfolist;
use App\Filament\Resources\MsUnits\Tables\MsUnitsTable;
use App\Models\MsUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsUnitResource extends Resource
{
    protected static ?string $model = MsUnit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|UnitEnum|null $navigationGroup = 'Item';

    protected static ?string $navigationLabel = 'Unit';

    protected static ?string $modelLabel = 'Unit';

    protected static ?string $pluralModelLabel = 'Unit';

    protected static ?string $slug = 'units';

    public static function form(Schema $schema): Schema
    {
        return MsUnitForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsUnitInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsUnitsTable::configure($table);
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
            'index' => ListMsUnits::route('/'),
            'create' => CreateMsUnit::route('/create'),
            'view' => ViewMsUnit::route('/{record}'),
            'edit' => EditMsUnit::route('/{record}/edit'),
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
