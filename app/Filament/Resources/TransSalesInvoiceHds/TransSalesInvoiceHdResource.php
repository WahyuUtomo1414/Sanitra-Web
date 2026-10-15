<?php

namespace App\Filament\Resources\TransSalesInvoiceHds;

use App\Filament\Resources\TransSalesInvoiceHds\Pages\ListTransSalesInvoiceHds;
use App\Filament\Resources\TransSalesInvoiceHds\Pages\ViewTransSalesInvoiceHd;
use App\Filament\Resources\TransSalesInvoiceHds\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\TransSalesInvoiceHds\Schemas\TransSalesInvoiceHdInfolist;
use App\Filament\Resources\TransSalesInvoiceHds\Tables\TransSalesInvoiceHdsTable;
use App\Models\TransSalesInvoiceHd;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TransSalesInvoiceHdResource extends Resource
{
    protected static ?string $model = TransSalesInvoiceHd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Invoice';

    protected static ?string $modelLabel = 'Invoice';

    protected static ?string $pluralModelLabel = 'Invoice';

    protected static ?string $slug = 'invoices';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransSalesInvoiceHdInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransSalesInvoiceHdsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransSalesInvoiceHds::route('/'),
            'view' => ViewTransSalesInvoiceHd::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
