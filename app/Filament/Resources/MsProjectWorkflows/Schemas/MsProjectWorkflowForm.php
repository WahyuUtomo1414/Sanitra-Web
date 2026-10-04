<?php

namespace App\Filament\Resources\MsProjectWorkflows\Schemas;

use App\Models\MsProjectType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsProjectWorkflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('type_id')
                        ->label('Tipe Project')
                        ->options(fn () => MsProjectType::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('name')
                        ->label('Nama Tahap')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('sequence')
                        ->label('Urutan')
                        ->integer()
                        ->minValue(1)
                        ->required(),

                    TextInput::make('progress_weight')
                        ->label('Bobot Progress')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->required()
                        ->helperText('Total bobot per tipe project harus 100% sebelum workflow dipakai.'),

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
