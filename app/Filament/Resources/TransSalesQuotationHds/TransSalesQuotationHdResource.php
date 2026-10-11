<?php

namespace App\Filament\Resources\TransSalesQuotationHds;

use App\Enums\Status\QuotationStatus;
use App\Filament\Resources\TransSalesQuotationHds\Pages\CreateTransSalesQuotationHd;
use App\Filament\Resources\TransSalesQuotationHds\Pages\EditTransSalesQuotationHd;
use App\Filament\Resources\TransSalesQuotationHds\Pages\ListTransSalesQuotationHds;
use App\Filament\Resources\TransSalesQuotationHds\Pages\ViewTransSalesQuotationHd;
use App\Filament\Resources\TransSalesQuotationHds\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\TransSalesQuotationHds\RelationManagers\TermsRelationManager;
use App\Filament\Resources\TransSalesQuotationHds\Schemas\TransSalesQuotationHdForm;
use App\Filament\Resources\TransSalesQuotationHds\Schemas\TransSalesQuotationHdInfolist;
use App\Filament\Resources\TransSalesQuotationHds\Tables\TransSalesQuotationHdsTable;
use App\Models\TransSalesQuotationHd;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TransSalesQuotationHdResource extends Resource
{
    protected static ?string $model = TransSalesQuotationHd::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Quotation';

    protected static ?string $modelLabel = 'Quotation';

    protected static ?string $pluralModelLabel = 'Quotation';

    protected static ?string $slug = 'quotations';

    public static function form(Schema $schema): Schema
    {
        return TransSalesQuotationHdForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransSalesQuotationHdInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransSalesQuotationHdsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            TermsRelationManager::class,
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record->status?->code === QuotationStatus::Draft->value;
    }

    public static function canDelete(Model $record): bool
    {
        return $record->status?->code === QuotationStatus::Draft->value;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransSalesQuotationHds::route('/'),
            'create' => CreateTransSalesQuotationHd::route('/create'),
            'view' => ViewTransSalesQuotationHd::route('/{record}'),
            'edit' => EditTransSalesQuotationHd::route('/{record}/edit'),
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
