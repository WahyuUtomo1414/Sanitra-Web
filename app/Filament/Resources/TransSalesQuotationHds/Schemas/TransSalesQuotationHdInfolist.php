<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Schemas;

use App\Enums\VatType;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransSalesQuotationHdInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')->columnSpanFull()->columns(2)->schema([
                TextEntry::make('code')->label('Nomor Quotation'),
                TextEntry::make('transaction_date')->label('Tanggal')->date('d M Y'),
                TextEntry::make('status.name')->label('Status')->badge(),
                TextEntry::make('client.name')->label('Klien'),
                TextEntry::make('company.name')->label('Perusahaan Penerbit'),
                TextEntry::make('projectType.name')->label('Tipe Proyek'),
                TextEntry::make('payment_type')->label('Skema Pembayaran'),
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
