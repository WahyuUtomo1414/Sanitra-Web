<?php

namespace App\Filament\Resources\MsItems;

use App\Filament\Resources\MsItems\Pages\CreateMsItem;
use App\Filament\Resources\MsItems\Pages\EditMsItem;
use App\Filament\Resources\MsItems\Pages\ListMsItems;
use App\Filament\Resources\MsItems\Pages\ViewMsItem;
use App\Filament\Resources\MsItems\RelationManagers\UnitsRelationManager;
use App\Filament\Resources\MsItems\Schemas\MsItemForm;
use App\Filament\Resources\MsItems\Schemas\MsItemInfolist;
use App\Filament\Resources\MsItems\Tables\MsItemsTable;
use App\Models\MsItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsItemResource extends Resource
{
    protected static ?string $model = MsItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Item';

    protected static ?string $navigationLabel = 'Item';

    protected static ?string $modelLabel = 'Item';

    protected static ?string $pluralModelLabel = 'Item';

    protected static ?string $slug = 'items';

    public static function form(Schema $schema): Schema
    {
        return MsItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UnitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMsItems::route('/'),
            'create' => CreateMsItem::route('/create'),
            'view' => ViewMsItem::route('/{record}'),
            'edit' => EditMsItem::route('/{record}/edit'),
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
