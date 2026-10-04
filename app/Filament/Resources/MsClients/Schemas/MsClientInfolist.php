<?php

namespace App\Filament\Resources\MsClients\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('code')
                        ->label('Kode'),
                    TextEntry::make('name')
                        ->label('Nama Klien'),
                    TextEntry::make('email')
                        ->label('Email'),
                    TextEntry::make('phone')
                        ->label('Telepon'),
                    TextEntry::make('address')
                        ->label('Alamat')
                        ->columnSpanFull(),
                    TextEntry::make('description')
                        ->label('Deskripsi')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    IconEntry::make('active')
                        ->label('Aktif')
                        ->boolean(),
                ]),

            Section::make('Relasi')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('picEmployee.name')
                        ->label('PIC Karyawan')
                        ->placeholder('-'),
                ]),

            Section::make('Audit Data')
                ->columnSpanFull()
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('createdBy.name')
                        ->label('Dibuat Oleh')
                        ->placeholder('-'),
                    TextEntry::make('created_at')
                        ->label('Dibuat Pada')
                        ->dateTime('d M Y H:i'),
                    TextEntry::make('updatedBy.name')
                        ->label('Diubah Oleh')
                        ->placeholder('-'),
                    TextEntry::make('updated_at')
                        ->label('Diubah Pada')
                        ->dateTime('d M Y H:i')
                        ->placeholder('-'),
                    TextEntry::make('deletedBy.name')
                        ->label('Dihapus Oleh')
                        ->placeholder('-')
                        ->visible(fn ($record) => $record?->deleted_at),
                    TextEntry::make('deleted_at')
                        ->label('Dihapus Pada')
                        ->dateTime('d M Y H:i')
                        ->visible(fn ($record) => $record?->deleted_at),
                ]),
        ]);
    }
}
