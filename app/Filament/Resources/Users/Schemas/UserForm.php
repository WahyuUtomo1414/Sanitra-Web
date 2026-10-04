<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\MsEmployee;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Akun')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(128),

                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->maxLength(255),
                ]),

            Section::make('Relasi')
                ->columnSpanFull()
                ->schema([
                    Select::make('employee_id')
                        ->label('Karyawan')
                        ->options(fn () => MsEmployee::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->unique(ignoreRecord: true)
                        ->nullable(),
                ]),

            Section::make('Media')
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('avatar')
                        ->label('Avatar')
                        ->image()
                        ->disk('public')
                        ->directory('users/avatar')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->nullable(),
                ]),
        ]);
    }
}
