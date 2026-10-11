<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Tables;

use App\Enums\Status\QuotationStatus;
use App\Models\TransSalesQuotationHd;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransSalesQuotationHdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('client.name')->label('Klien')->searchable(),
                TextColumn::make('grand_total')->label('Grand Total')->money('IDR')->sortable(),
                TextColumn::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesQuotationHd $record): string => QuotationStatus::from($record->status->code)->color()),
                TextColumn::make('createdBy.name')->label('Dibuat Oleh')->toggleable(),
                TextColumn::make('updatedBy.name')->label('Diubah Oleh')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_id')->label('Status')->relationship('status', 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->where('type', QuotationStatus::type()->value)),
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
                EditAction::make()->visible(fn (TransSalesQuotationHd $record): bool => $record->status?->code === QuotationStatus::Draft->value),
            ]);
    }
}
