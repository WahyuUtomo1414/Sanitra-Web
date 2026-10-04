<?php

namespace App\Filament\Resources\MsItems\Schemas;

use App\Models\MsItemCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsItemForm
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
                        ->label('Nama Item')
                        ->required()
                        ->maxLength(128),

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
                    Select::make('item_category_id')
                        ->label('Kategori Item')
                        ->options(fn () => MsItemCategory::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),

            Section::make('Media')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    FileUpload::make('image')
                        ->label('Gambar')
                        ->image()
                        ->disk('public')
                        ->directory('items')
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
                        ->default(true),
                ]),
        ]);
    }
}
