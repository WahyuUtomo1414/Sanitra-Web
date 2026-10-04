<?php

namespace App\Filament\Resources\MsEmployees\Schemas;

use App\Models\MsCompany;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->columnSpanFull()
                        ->label('Kode')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Akan di-generate otomatis saat disimpan'),

                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(128),

                    TextInput::make('position')
                        ->label('Jabatan')
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
                ]),

            Section::make('Relasi')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('company_id')
                        ->label('Perusahaan')
                        ->options(fn () => MsCompany::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
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
