<?php

namespace App\Filament\Resources\TransSalesPayments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\Status\PaymentStatus;
use App\Models\TransSalesPayment;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class TransSalesPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pembayaran')->columnSpanFull()->columns(2)->schema([
                TextEntry::make('code')->label('Nomor Pembayaran'),
                TextEntry::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesPayment $record): string => PaymentStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
                TextEntry::make('invoice.code')->label('Invoice'),
                TextEntry::make('transaction_date')->label('Tanggal')->date('d M Y'),
                TextEntry::make('amount')->label('Nominal')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextEntry::make('payment_method')->label('Metode')
                    ->formatStateUsing(fn (PaymentMethod $state): string => $state->label()),
                TextEntry::make('reference_no')->label('Nomor Referensi')->placeholder('-'),
                TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
            ]),
            Section::make('Bukti Pembayaran')->columnSpanFull()->schema([
                ImageEntry::make('payment_proof')->label('Gambar Bukti')->disk('public')
                    ->visible(fn (TransSalesPayment $record): bool => $record->payment_proof !== null
                        && ! str_ends_with(strtolower($record->payment_proof), '.pdf')),
                TextEntry::make('payment_proof')->label('PDF Bukti')
                    ->formatStateUsing(fn (): string => 'Buka bukti pembayaran')
                    ->url(fn (TransSalesPayment $record): ?string => $record->payment_proof
                        ? Storage::disk('public')->url($record->payment_proof) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (TransSalesPayment $record): bool => $record->payment_proof !== null
                        && str_ends_with(strtolower($record->payment_proof), '.pdf')),
                TextEntry::make('proof_missing')->label('Bukti')->default('Belum diunggah')
                    ->visible(fn (TransSalesPayment $record): bool => $record->payment_proof === null),
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
