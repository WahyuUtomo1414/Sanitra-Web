<?php

namespace App\Filament\Resources\TransSalesOrderHds\RelationManagers;

use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Sales Order';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Item')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('item.name')->label('Item'),
                        TextEntry::make('unit.name')->label('Unit'),
                        TextEntry::make('qty')->label('Jumlah'),
                        TextEntry::make('unit_price')->label('Harga Satuan')
                            ->formatStateUsing(fn(int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                        TextEntry::make('discount')->label('Diskon')
                            ->formatStateUsing(fn(int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                        TextEntry::make('total_price')->label('Total')
                            ->formatStateUsing(fn(int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                        TextEntry::make('notes')->label('Catatan')->columnSpanFull()
                            ->placeholder('—'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('item.name')->label('Item')->searchable(),
                TextColumn::make('unit.name')->label('Unit'),
                TextColumn::make('qty')->label('Jumlah'),
                TextColumn::make('unit_price')->label('Harga Satuan')
                    ->formatStateUsing(fn(int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextColumn::make('discount')->label('Diskon')
                    ->formatStateUsing(fn(int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextColumn::make('total_price')->label('Total')
                    ->formatStateUsing(fn(int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
            ]);
    }
}
