<?php

namespace App\Filament\Resources\TransSalesPayments;

use App\Filament\Resources\TransSalesPayments\Pages\CreateTransSalesPayment;
use App\Filament\Resources\TransSalesPayments\Pages\ListTransSalesPayments;
use App\Filament\Resources\TransSalesPayments\Pages\ViewTransSalesPayment;
use App\Filament\Resources\TransSalesPayments\Schemas\TransSalesPaymentForm;
use App\Filament\Resources\TransSalesPayments\Schemas\TransSalesPaymentInfolist;
use App\Filament\Resources\TransSalesPayments\Tables\TransSalesPaymentsTable;
use App\Models\TransSalesPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TransSalesPaymentResource extends Resource
{
    protected static ?string $model = TransSalesPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    protected static ?string $slug = 'payments';

    public static function form(Schema $schema): Schema
    {
        return TransSalesPaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransSalesPaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransSalesPaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
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

    public static function getPages(): array
    {
        return [
            'index' => ListTransSalesPayments::route('/'),
            'create' => CreateTransSalesPayment::route('/create'),
            'view' => ViewTransSalesPayment::route('/{record}'),
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
