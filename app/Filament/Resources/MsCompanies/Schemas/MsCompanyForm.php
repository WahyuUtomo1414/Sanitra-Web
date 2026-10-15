<?php

namespace App\Filament\Resources\MsCompanies\Schemas;

use App\Models\MsEmployee;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsCompanyForm
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

                    TextInput::make('legal_name')
                        ->label('Nama Legal')
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

                    TextInput::make('website')
                        ->label('Website')
                        ->url()
                        ->nullable()
                        ->maxLength(255),

                    Textarea::make('address')
                        ->label('Alamat')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    TextInput::make('postal_code')
                        ->label('Kode Pos')
                        ->required()
                        ->maxLength(10),

                    Textarea::make('description')
                        ->columnSpanFull()
                        ->label('Deskripsi')
                        ->nullable()
                        ->rows(3),
                ]),

            Section::make('Relasi')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('pic_employee_id')
                        ->label('PIC Karyawan')
                        ->options(fn ($record) => $record ? MsEmployee::query()
                            ->where('company_id', $record->getKey())
                            ->orderBy('name')
                            ->pluck('name', 'id') : [])
                        ->searchable()
                        ->preload()
                        ->visible(fn ($record) => $record !== null)
                        ->nullable(),
                ]),

            Section::make('Identitas Visual')
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->disk('public')
                        ->directory('companies/logos')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->nullable(),
                ]),

            Section::make('Status')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Toggle::make('active')
                        ->label('Aktif')
                        ->default(true)
                        ->disabled(fn ($record) => $record?->getKey() === 1),
                ]),
        ]);
    }
}
