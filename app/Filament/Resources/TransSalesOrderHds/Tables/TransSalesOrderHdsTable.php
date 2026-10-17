<?php

namespace App\Filament\Resources\TransSalesOrderHds\Tables;

use App\Enums\Status\OrderStatus;
use App\Models\TransSalesOrderHd;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransSalesOrderHdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('quotation.code')->label('Quotation')->searchable(),
                TextColumn::make('client.name')->label('Klien')->searchable(),
                TextColumn::make('grand_total')->label('Grand Total')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state))
                    ->sortable(),
                TextColumn::make('status.name')->label('Status Order')->badge()
                    ->color(fn (TransSalesOrderHd $record): string => OrderStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
                TextColumn::make('payment_summary')->label('Status Bayar')
                    ->state(fn (TransSalesOrderHd $record): string => $record->paymentSummary()),
                TextColumn::make('createdBy.name')->label('Dibuat Oleh')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updatedBy.name')->label('Diubah Oleh')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_id')->label('Status Order')->relationship('status', 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->where('type', OrderStatus::type()->value)),
                SelectFilter::make('client_id')->label('Klien')->relationship('client', 'name')->searchable(),
                SelectFilter::make('company_id')->label('Perusahaan')->relationship('company', 'name')->searchable(),
                Filter::make('transaction_date')->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('transaction_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('transaction_date', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->label('Ubah Catatan'),
            ]);
    }
}
