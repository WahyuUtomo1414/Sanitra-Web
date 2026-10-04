<?php

namespace App\Filament\Resources\MsBankAccounts;

use App\Filament\Resources\MsBankAccounts\Pages\CreateMsBankAccount;
use App\Filament\Resources\MsBankAccounts\Pages\EditMsBankAccount;
use App\Filament\Resources\MsBankAccounts\Pages\ListMsBankAccounts;
use App\Filament\Resources\MsBankAccounts\Pages\ViewMsBankAccount;
use App\Filament\Resources\MsBankAccounts\Schemas\MsBankAccountForm;
use App\Filament\Resources\MsBankAccounts\Schemas\MsBankAccountInfolist;
use App\Filament\Resources\MsBankAccounts\Tables\MsBankAccountsTable;
use App\Models\MsBankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsBankAccountResource extends Resource
{
    protected static ?string $model = MsBankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|UnitEnum|null $navigationGroup = 'Perusahaan';

    protected static ?string $navigationLabel = 'Rekening Bank';

    protected static ?string $modelLabel = 'Rekening Bank';

    protected static ?string $pluralModelLabel = 'Rekening Bank';

    protected static ?string $slug = 'bank-accounts';

    public static function form(Schema $schema): Schema
    {
        return MsBankAccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsBankAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsBankAccountsTable::configure($table);
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
            'index' => ListMsBankAccounts::route('/'),
            'create' => CreateMsBankAccount::route('/create'),
            'view' => ViewMsBankAccount::route('/{record}'),
            'edit' => EditMsBankAccount::route('/{record}/edit'),
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
