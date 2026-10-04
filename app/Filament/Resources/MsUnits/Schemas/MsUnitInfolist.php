<?php

namespace App\Filament\Resources\MsUnits\Schemas;

use App\Enums\UnitType;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsUnitInfolist
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
                        ->label('Nama'),
                    TextEntry::make('type')
                        ->label('Tipe')
                        ->formatStateUsing(fn (string $state): string => UnitType::tryFrom($state)?->label() ?? $state),
                    TextEntry::make('description')
                        ->label('Deskripsi')
                        ->placeholder('-')
                        ->columnSpanFull(),
                    IconEntry::make('active')
                        ->label('Aktif')
                        ->boolean(),
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
