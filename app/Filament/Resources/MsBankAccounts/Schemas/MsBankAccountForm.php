<?php

namespace App\Filament\Resources\MsBankAccounts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsBankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Akan di-generate otomatis saat disimpan'),

                    TextInput::make('name')
                        ->label('Label Rekening')
                        ->required()
                        ->maxLength(128),

                    TextInput::make('bank_name')
                        ->label('Nama Bank')
                        ->required()
                        ->maxLength(128),

                    TextInput::make('account_number')
                        ->label('Nomor Rekening')
                        ->required()
                        ->maxLength(128)
                        ->helperText('Disimpan sebagai string agar nol awal tidak hilang.'),

                    TextInput::make('account_name')
                        ->label('Nama Pemilik Rekening')
                        ->required()
                        ->maxLength(128),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->label('Deskripsi')
                        ->nullable()
                        ->rows(3),
                ]),

            Section::make('Status')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Toggle::make('active')
                        ->label('Aktif')
                        ->default(true),
                ]),
        ]);
    }
}
