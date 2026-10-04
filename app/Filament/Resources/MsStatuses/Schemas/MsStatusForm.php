<?php

namespace App\Filament\Resources\MsStatuses\Schemas;

use App\Enums\StatusType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsStatusForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->label('Tipe Entitas')
                        ->options(collect(StatusType::cases())
                            ->mapWithKeys(fn (StatusType $case) => [$case->value => $case->value]))
                        ->required()
                        ->native(false)
                        ->helperText('Tipe entitas yang menggunakan status ini (quotation, project, dsb.).'),

                    TextInput::make('code')
                        ->label('Kode')
                        ->required()
                        ->maxLength(128)
                        ->helperText('Kode stabil untuk logika aplikasi, mis. draft, approved, ready_to_bill. Kombinasi (tipe, kode) harus unik.'),

                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(128)
                        ->helperText('Label yang tampil di UI.'),

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
