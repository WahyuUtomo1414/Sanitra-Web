<?php

namespace App\Filament\Resources\TransSalesOrderHds\Schemas;

use App\Enums\Status\OrderPaymentStatus;
use App\Enums\Status\OrderStatus;
use App\Enums\VatType;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransSalesOrderHdInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Sales Order')->columnSpanFull()->columns(2)->schema([
                TextEntry::make('code')->label('Nomor Sales Order'),
                TextEntry::make('transaction_date')->label('Tanggal')->date('d M Y'),
                TextEntry::make('quotation.code')->label('Quotation Sumber'),
                TextEntry::make('client.name')->label('Klien'),
                TextEntry::make('company.legal_name')->label('Perusahaan Penerbit'),
                TextEntry::make('projectType.name')->label('Tipe Proyek'),
                TextEntry::make('payment_type')->label('Skema Pembayaran'),
                TextEntry::make('status.name')->label('Status Order')->badge()
                    ->color(fn ($record): string => OrderStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
                TextEntry::make('paymentStatus.name')->label('Status Pembayaran')->badge()
                    ->color(fn ($record): string => OrderPaymentStatus::tryFrom($record->paymentStatus?->code ?? '')?->color() ?? 'gray'),
                TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Nominal')->columnSpanFull()->columns(2)->schema([
                TextEntry::make('vat_type')->label('Jenis PPN')
                    ->formatStateUsing(fn (VatType $state): string => $state->label()),
                TextEntry::make('discount_total')->label('Total Diskon')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextEntry::make('subtotal')->label('Subtotal')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextEntry::make('vat_amount')->label('PPN')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextEntry::make('grand_total')->label('Grand Total')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
            ]),
            Section::make('Audit Data')->columnSpanFull()->columns(2)->collapsible()->schema([
                TextEntry::make('createdBy.name')->label('Dibuat Oleh')->placeholder('-'),
                TextEntry::make('created_at')->label('Dibuat Pada')->dateTime('d M Y H:i'),
                TextEntry::make('updatedBy.name')->label('Diubah Oleh')->placeholder('-'),
                TextEntry::make('updated_at')->label('Diubah Pada')->dateTime('d M Y H:i')->placeholder('-'),
            ]),
        ]);
    }
}
