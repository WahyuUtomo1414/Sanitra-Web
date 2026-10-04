<?php

namespace App\Filament\Resources\MsCompanies;

use App\Filament\Resources\MsCompanies\Pages\CreateMsCompany;
use App\Filament\Resources\MsCompanies\Pages\EditMsCompany;
use App\Filament\Resources\MsCompanies\Pages\ListMsCompanies;
use App\Filament\Resources\MsCompanies\Pages\ViewMsCompany;
use App\Filament\Resources\MsCompanies\Schemas\MsCompanyForm;
use App\Filament\Resources\MsCompanies\Schemas\MsCompanyInfolist;
use App\Filament\Resources\MsCompanies\Tables\MsCompaniesTable;
use App\Models\MsCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsCompanyResource extends Resource
{
    protected static ?string $model = MsCompany::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Perusahaan';

    protected static ?string $navigationLabel = 'Perusahaan';

    protected static ?string $modelLabel = 'Perusahaan';

    protected static ?string $pluralModelLabel = 'Perusahaan';

    protected static ?string $slug = 'companies';

    public static function form(Schema $schema): Schema
    {
        return MsCompanyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsCompanyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsCompaniesTable::configure($table);
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
            'index' => ListMsCompanies::route('/'),
            'create' => CreateMsCompany::route('/create'),
            'view' => ViewMsCompany::route('/{record}'),
            'edit' => EditMsCompany::route('/{record}/edit'),
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
