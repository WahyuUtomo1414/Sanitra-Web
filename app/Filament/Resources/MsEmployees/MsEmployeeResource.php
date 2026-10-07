<?php

namespace App\Filament\Resources\MsEmployees;

use App\Filament\Resources\MsEmployees\Pages\CreateMsEmployee;
use App\Filament\Resources\MsEmployees\Pages\EditMsEmployee;
use App\Filament\Resources\MsEmployees\Pages\ListMsEmployees;
use App\Filament\Resources\MsEmployees\Pages\ViewMsEmployee;
use App\Filament\Resources\MsEmployees\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\MsEmployees\Schemas\MsEmployeeForm;
use App\Filament\Resources\MsEmployees\Schemas\MsEmployeeInfolist;
use App\Filament\Resources\MsEmployees\Tables\MsEmployeesTable;
use App\Models\MsEmployee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsEmployeeResource extends Resource
{
    protected static ?string $model = MsEmployee::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Perusahaan';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $modelLabel = 'Karyawan';

    protected static ?string $pluralModelLabel = 'Karyawan';

    protected static ?string $slug = 'employees';

    public static function form(Schema $schema): Schema
    {
        return MsEmployeeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsEmployeeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsEmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMsEmployees::route('/'),
            'create' => CreateMsEmployee::route('/create'),
            'view' => ViewMsEmployee::route('/{record}'),
            'edit' => EditMsEmployee::route('/{record}/edit'),
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
