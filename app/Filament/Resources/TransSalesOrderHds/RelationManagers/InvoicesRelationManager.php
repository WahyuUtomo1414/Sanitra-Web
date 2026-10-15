<?php

namespace App\Filament\Resources\TransSalesOrderHds\RelationManagers;

use App\Enums\Status\InvoiceStatus;
use App\Filament\Resources\TransSalesInvoiceHds\TransSalesInvoiceHdResource;
use App\Models\TransSalesInvoiceHd;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Invoice';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y'),
                TextColumn::make('due_date')->label('Jatuh Tempo')->date('d M Y'),
                TextColumn::make('grand_total')->label('Total')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextColumn::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesInvoiceHd $record): string => InvoiceStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
            ])
            ->recordActions([
                Action::make('viewInvoice')->label('Lihat Invoice')->icon('heroicon-o-eye')
                    ->url(fn (TransSalesInvoiceHd $record): string => TransSalesInvoiceHdResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
