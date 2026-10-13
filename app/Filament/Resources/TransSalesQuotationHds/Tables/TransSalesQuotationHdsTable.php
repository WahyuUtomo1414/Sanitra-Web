<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Tables;

use App\Enums\AutoCodePrefix;
use App\Enums\Status\QuotationStatus;
use App\Models\TransSalesQuotationHd;
use App\Services\Shared\GenerateCodeService;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TransSalesQuotationHdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('client.name')->label('Klien')->searchable(),
                TextColumn::make('grand_total')->label('Grand Total')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state))
                    ->sortable(),
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
                DeleteAction::make()
                    ->visible(fn (TransSalesQuotationHd $record): bool => $record->status?->code === QuotationStatus::Draft->value)
                    ->using(fn (TransSalesQuotationHd $record): bool => self::discardDraft($record)),
            ]);
    }

    public static function discardDraft(TransSalesQuotationHd $record): bool
    {
        return DB::transaction(function () use ($record): bool {
            $code = $record->code;
            $record->items()->forceDelete();
            $record->terms()->forceDelete();
            $deleted = (bool) $record->forceDelete();

            app(GenerateCodeService::class)
                ->releaseIfLatest(AutoCodePrefix::TransSalesQuotationHd, $code);

            return $deleted;
        });
    }
}
