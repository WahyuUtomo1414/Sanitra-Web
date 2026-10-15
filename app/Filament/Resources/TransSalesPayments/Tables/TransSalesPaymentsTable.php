<?php

namespace App\Filament\Resources\TransSalesPayments\Tables;

use App\Enums\PaymentMethod;
use App\Enums\Status\PaymentStatus;
use App\Models\TransSalesPayment;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransSalesPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('invoice.code')->label('Invoice')->searchable(),
                TextColumn::make('amount')->label('Nominal')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state))
                    ->sortable(),
                TextColumn::make('payment_method')->label('Metode')
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                TextColumn::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesPayment $record): string => PaymentStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
                TextColumn::make('reference_no')->label('Referensi')->placeholder('-'),
                TextColumn::make('createdBy.name')->label('Dibuat Oleh')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updatedBy.name')->label('Diubah Oleh')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_id')->label('Status')
                    ->relationship('status', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query
                        ->where('type', PaymentStatus::type()->value)),
                SelectFilter::make('payment_method')->label('Metode')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])->all()),
                Filter::make('transaction_date')->label('Rentang Tanggal')->schema([
                    DatePicker::make('from')->label('Dari'),
                    DatePicker::make('until')->label('Sampai'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('transaction_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('transaction_date', '<=', $date))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
