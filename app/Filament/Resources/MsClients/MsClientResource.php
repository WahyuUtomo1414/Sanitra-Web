<?php

namespace App\Filament\Resources\MsClients;

use App\Filament\Resources\MsClients\Pages\CreateMsClient;
use App\Filament\Resources\MsClients\Pages\EditMsClient;
use App\Filament\Resources\MsClients\Pages\ListMsClients;
use App\Filament\Resources\MsClients\Pages\ViewMsClient;
use App\Filament\Resources\MsClients\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\MsClients\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\MsClients\Schemas\MsClientForm;
use App\Filament\Resources\MsClients\Schemas\MsClientInfolist;
use App\Filament\Resources\MsClients\Tables\MsClientsTable;
use App\Models\MsClient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MsClientResource extends Resource
{
    protected static ?string $model = MsClient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Perusahaan';

    protected static ?string $navigationLabel = 'Klien';

    protected static ?string $modelLabel = 'Klien';

    protected static ?string $pluralModelLabel = 'Klien';

    protected static ?string $slug = 'clients';

    public static function form(Schema $schema): Schema
    {
        return MsClientForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MsClientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MsClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ContactsRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMsClients::route('/'),
            'create' => CreateMsClient::route('/create'),
            'view' => ViewMsClient::route('/{record}'),
            'edit' => EditMsClient::route('/{record}/edit'),
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
