<?php

namespace App\Filament\Resources\MsProjectTypes;

use App\Filament\Resources\MsProjectTypes\Pages\CreateMsProjectType;
use App\Filament\Resources\MsProjectTypes\Pages\EditMsProjectType;
use App\Filament\Resources\MsProjectTypes\Pages\ListMsProjectTypes;
use App\Filament\Resources\MsProjectTypes\Pages\ViewMsProjectType;
use App\Filament\Resources\MsProjectTypes\Schemas\MsProjectTypeForm;
use App\Filament\Resources\MsProjectTypes\Schemas\MsProjectTypeInfolist;
use App\Filament\Resources\MsProjectTypes\Tables\MsProjectTypesTable;
use App\Models\MsProjectType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsProjectTypeResource extends Resource
{
    protected static ?string $model = MsProjectType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|UnitEnum|null $navigationGroup = 'Project';

    protected static ?string $navigationLabel = 'Tipe Project';

    protected static ?string $modelLabel = 'Tipe Project';

    protected static ?string $pluralModelLabel = 'Tipe Project';

    protected static ?string $slug = 'project-types';

    public static function form(Schema $schema): Schema
    {
        return MsProjectTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsProjectTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsProjectTypesTable::configure($table);
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
            'index' => ListMsProjectTypes::route('/'),
            'create' => CreateMsProjectType::route('/create'),
            'view' => ViewMsProjectType::route('/{record}'),
            'edit' => EditMsProjectType::route('/{record}/edit'),
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
