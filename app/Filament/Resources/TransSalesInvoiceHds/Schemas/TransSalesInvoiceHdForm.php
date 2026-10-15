<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TransSalesInvoiceHdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Invoice')->columnSpanFull()->columns(2)->schema([
                TextInput::make('code')->label('Nomor Invoice')->disabled()->dehydrated(false),
                TextInput::make('grand_total')->label('Total Tagihan')->disabled()->dehydrated(false),
                DatePicker::make('transaction_date')->label('Tanggal Invoice')->required(),
                DatePicker::make('due_date')->label('Jatuh Tempo')->required()->afterOrEqual('transaction_date'),
                Select::make('bank_account_id')->label('Rekening Tujuan')
                    ->relationship('bankAccount', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true))
                    ->searchable()->preload()->required(),
                Textarea::make('notes')->label('Catatan')->nullable()->columnSpanFull(),
            ]),
        ]);
    }
}
