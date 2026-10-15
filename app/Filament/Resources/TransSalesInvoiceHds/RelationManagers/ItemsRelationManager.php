<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\RelationManagers;

use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Termin yang Ditagih';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('sequence')->label('Urutan')->sortable(),
                TextColumn::make('salesTerm.term_name')->label('Termin'),
                TextColumn::make('percentage')->label('Persentase')->suffix('%'),
                TextColumn::make('amount')->label('Nilai')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
            ]);
    }
}
