<?php

namespace App\Filament\Resources\MsUnits\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->helperText('Kode singkat bermakna, misal KG, PCS, LTR, M2. Diisi manual.'),

                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(128),

                    TextInput::make('type')
                        ->label('Tipe')
                        ->required()
                        ->maxLength(50)
                        ->helperText('Mis. berat, panjang, volume, satuan.'),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->nullable()
                        ->rows(3),
                ]),

            Section::make('Status')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('active')
                        ->label('Aktif')
                        ->default(true),
                ]),
        ]);
    }
}
