<?php

namespace App\Filament\Resources\MsItemCategories;

use App\Filament\Resources\MsItemCategories\Pages\CreateMsItemCategory;
use App\Filament\Resources\MsItemCategories\Pages\EditMsItemCategory;
use App\Filament\Resources\MsItemCategories\Pages\ListMsItemCategories;
use App\Filament\Resources\MsItemCategories\Pages\ViewMsItemCategory;
use App\Filament\Resources\MsItemCategories\Schemas\MsItemCategoryForm;
use App\Filament\Resources\MsItemCategories\Schemas\MsItemCategoryInfolist;
use App\Filament\Resources\MsItemCategories\Tables\MsItemCategoriesTable;
use App\Models\MsItemCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsItemCategoryResource extends Resource
{
    protected static ?string $model = MsItemCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Item';

    protected static ?string $navigationLabel = 'Kategori Item';

    protected static ?string $modelLabel = 'Kategori Item';

    protected static ?string $pluralModelLabel = 'Kategori Item';

    protected static ?string $slug = 'item-categories';

    public static function form(Schema $schema): Schema
    {
        return MsItemCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsItemCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsItemCategoriesTable::configure($table);
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
            'index' => ListMsItemCategories::route('/'),
            'create' => CreateMsItemCategory::route('/create'),
            'view' => ViewMsItemCategory::route('/{record}'),
            'edit' => EditMsItemCategory::route('/{record}/edit'),
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
