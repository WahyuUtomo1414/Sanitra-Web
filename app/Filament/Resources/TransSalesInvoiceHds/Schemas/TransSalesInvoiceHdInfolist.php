<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Schemas;

use App\Enums\Status\InvoiceStatus;
use App\Enums\VatType;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransSalesInvoiceHdInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Invoice')->columnSpanFull()->columns(2)->schema([
                TextEntry::make('code')->label('Nomor Invoice'),
                TextEntry::make('status.name')->label('Status')->badge()
                    ->color(fn ($record): string => InvoiceStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
                TextEntry::make('transaction_date')->label('Tanggal')->date('d M Y'),
                TextEntry::make('due_date')->label('Jatuh Tempo')->date('d M Y'),
                TextEntry::make('order.code')->label('Sales Order'),
                TextEntry::make('client.name')->label('Klien'),
                TextEntry::make('company.legal_name')->label('Perusahaan'),
                TextEntry::make('bankAccount.name')->label('Rekening Tujuan'),
                TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Nominal')->columnSpanFull()->columns(2)->schema([
                TextEntry::make('vat_type')->label('Jenis PPN')
                    ->formatStateUsing(fn (VatType $state): string => $state->label()),
                TextEntry::make('subtotal')->label('Subtotal')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextEntry::make('vat_amount')->label('PPN')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextEntry::make('grand_total')->label('Total Invoice')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
            ]),
        ]);
    }
}
