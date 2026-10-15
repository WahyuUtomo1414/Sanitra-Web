<?php

namespace App\Filament\Resources\TransSalesOrderHds;

use App\Filament\Resources\TransSalesOrderHds\Pages\CreateOrderInvoice;
use App\Filament\Resources\TransSalesOrderHds\Pages\CreateOrderProject;
use App\Filament\Resources\TransSalesOrderHds\Pages\EditTransSalesOrderHd;
use App\Filament\Resources\TransSalesOrderHds\Pages\ListTransSalesOrderHds;
use App\Filament\Resources\TransSalesOrderHds\Pages\ViewTransSalesOrderHd;
use App\Filament\Resources\TransSalesOrderHds\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\TransSalesOrderHds\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\TransSalesOrderHds\RelationManagers\TermsRelationManager;
use App\Filament\Resources\TransSalesOrderHds\Schemas\TransSalesOrderHdForm;
use App\Filament\Resources\TransSalesOrderHds\Schemas\TransSalesOrderHdInfolist;
use App\Filament\Resources\TransSalesOrderHds\Tables\TransSalesOrderHdsTable;
use App\Models\TransSalesOrderHd;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TransSalesOrderHdResource extends Resource
{
    protected static ?string $model = TransSalesOrderHd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Sales Order';

    protected static ?string $modelLabel = 'Sales Order';

    protected static ?string $pluralModelLabel = 'Sales Order';

    protected static ?string $slug = 'sales-orders';

    public static function form(Schema $schema): Schema
    {
        return TransSalesOrderHdForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransSalesOrderHdInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransSalesOrderHdsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            TermsRelationManager::class,
            InvoicesRelationManager::class,
        ];
    }

    public static function canCreate(): bool
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

    public static function getPages(): array
    {
        return [
            'index' => ListTransSalesOrderHds::route('/'),
            'view' => ViewTransSalesOrderHd::route('/{record}'),
            'edit' => EditTransSalesOrderHd::route('/{record}/edit'),
            'createInvoice' => CreateOrderInvoice::route('/{record}/create-invoice'),
            'createProject' => CreateOrderProject::route('/{record}/create-project'),
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
