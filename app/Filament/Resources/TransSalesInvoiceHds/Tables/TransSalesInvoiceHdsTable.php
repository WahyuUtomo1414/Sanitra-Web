<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Tables;

use App\Enums\Status\InvoiceStatus;
use App\Models\TransSalesInvoiceHd;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TransSalesInvoiceHdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('due_date')->label('Jatuh Tempo')->date('d M Y')->sortable(),
                TextColumn::make('order.code')->label('Sales Order')->searchable(),
                TextColumn::make('client.name')->label('Klien')->searchable(),
                TextColumn::make('grand_total')->label('Total')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state))
                    ->sortable(),
                TextColumn::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesInvoiceHd $record): string => InvoiceStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
