<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Schemas;

use App\Enums\VatType;
use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsProjectType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TransSalesQuotationHdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')->columnSpanFull()->columns(2)->schema([
                TextInput::make('code')->label('Nomor Quotation')->disabled()->dehydrated(false)
                    ->placeholder('Dibuat otomatis saat disimpan'),
                DatePicker::make('transaction_date')->label('Tanggal')->default(now())->required(),
                TextInput::make('payment_type')->label('Skema Pembayaran')->required()->maxLength(50),
            ]),
            Section::make('Relasi')->columnSpanFull()->columns(2)->schema([
                Select::make('company_id')->label('Perusahaan Penerbit')
                    ->options(fn () => MsCompany::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('project_type_id', null);
                    }),
                Select::make('project_type_id')->label('Tipe Proyek')
                    ->options(fn (Get $get) => MsProjectType::query()->where('active', true)
                        ->where('company_id', $get('company_id'))->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->preload()->required(),
                Select::make('client_id')->label('Klien')
                    ->options(fn () => MsClient::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->preload()->required(),
            ]),
            Section::make('PPN & Diskon')->columnSpanFull()->columns(2)->schema([
                Select::make('vat_type')->label('PPN')
                    ->options(collect(VatType::cases())->mapWithKeys(
                        fn (VatType $type): array => [$type->value => $type->label()],
                    )->all())
                    ->default(VatType::None->value)->required(),
                TextInput::make('discount_total')->label('Total Diskon')->disabled()->dehydrated(false)
                    ->prefix('Rp'),
                TextInput::make('subtotal')->label('Subtotal Setelah Diskon')->disabled()->dehydrated(false)
                    ->prefix('Rp'),
                TextInput::make('vat_amount')->label('Nilai PPN')->disabled()->dehydrated(false)
                    ->prefix('Rp'),
                TextInput::make('grand_total')->label('Grand Total')->disabled()->dehydrated(false)
                    ->prefix('Rp'),
            ]),
            Section::make('Catatan')->columnSpanFull()->schema([
                Textarea::make('notes')->label('Catatan')->rows(3),
            ]),
        ]);
    }
}
