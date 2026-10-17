<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\MsEmployee;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Akun')
                ->columnSpanFull()
                ->columns(2)
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
                ->columns(2)
                ->schema([
                    Select::make('employee_id')
                        ->label('Karyawan')
                        ->options(fn () => MsEmployee::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->unique(ignoreRecord: true)
                        ->nullable(),

                    CheckboxList::make('roles')
                        ->label('Role')
                        ->relationship('roles', 'name')
                        ->options(fn () => Role::query()->whereNot('name', 'super_admin')->orderBy('name')->pluck('name', 'id'))
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Media')
                ->columnSpanFull()
                ->columns(2)
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
