<?php

namespace App\Filament\Resources\MsClients\Schemas;

use App\Models\MsEmployee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('code')
                        ->label('Kode')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Akan di-generate otomatis saat disimpan'),

                    TextInput::make('name')
                        ->label('Nama Klien')
                        ->required()
                        ->maxLength(128),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(128),

                    TextInput::make('phone')
                        ->label('Telepon')
                        ->tel()
                        ->required()
                        ->maxLength(18),

                    Textarea::make('address')
                        ->label('Alamat')
                        ->required()
                        ->rows(2)
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->nullable()
                        ->rows(3),
                ]),

            Section::make('Relasi')
                ->columnSpanFull()
                ->schema([
                    Select::make('pic_employee_id')
                        ->label('PIC Karyawan')
                        ->options(fn () => MsEmployee::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->nullable(),
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
