<?php

namespace App\Filament\Resources\UserProfiles\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Akun')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nama'),
                    TextEntry::make('email')
                        ->label('Email'),
                    TextEntry::make('employee.name')
                        ->label('Karyawan')
                        ->placeholder('-'),
                ]),
            Section::make('Foto Profil')
                ->columnSpanFull()
                ->schema([
                    ImageEntry::make('avatar')
                        ->label('Avatar')
                        ->disk('public')
                        ->circular(),
                ]),
        ]);
    }
}
