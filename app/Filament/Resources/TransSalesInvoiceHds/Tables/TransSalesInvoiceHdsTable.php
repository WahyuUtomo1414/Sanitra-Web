<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Tables;

use App\Enums\Status\InvoiceStatus;
use App\Models\TransSalesInvoiceHd;
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

class TransSalesInvoiceHdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('due_date')->label('Jatuh Tempo')->date('d M Y')->sortable()
                    ->color(fn (TransSalesInvoiceHd $record): ?string => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('order.code')->label('Sales Order')->searchable(),
                TextColumn::make('client.name')->label('Klien')->searchable(),
                TextColumn::make('grand_total')->label('Total')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state))
                    ->sortable(),
                TextColumn::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesInvoiceHd $record): string => InvoiceStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
                TextColumn::make('createdBy.name')->label('Dibuat Oleh')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updatedBy.name')->label('Diubah Oleh')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_id')->label('Status')
                    ->relationship('status', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->where('type', InvoiceStatus::type()->value)),
                SelectFilter::make('client_id')->label('Klien')->relationship('client', 'name')->searchable(),
                SelectFilter::make('company_id')->label('Perusahaan')->relationship('company', 'name')->searchable(),
                Filter::make('due_date')->label('Rentang Jatuh Tempo')->schema([
                    DatePicker::make('from')->label('Dari'),
                    DatePicker::make('until')->label('Sampai'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_date', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
