<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Activitylog\Models\Activity;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Aktivitas')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('event')
                        ->label('Event')
                        ->badge(),
                    TextEntry::make('subject_type')
                        ->label('Tipe Entitas')
                        ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                    TextEntry::make('subject_id')
                        ->label('Record ID'),
                    TextEntry::make('causer.name')
                        ->label('Dilakukan Oleh')
                        ->placeholder('Sistem'),
                    TextEntry::make('created_at')
                        ->label('Waktu')
                        ->dateTime('d M Y H:i'),
                ]),

            Section::make('Nilai Sesudah')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    KeyValueEntry::make('attributes')
                        ->label('Fields')
                        ->columnSpanFull()
                        ->state(fn (Activity $record): array => $record->attribute_changes?->get('attributes', []) ?? [])
                        ->keyLabel('Field')
                        ->valueLabel('Nilai Sesudah')
                        ->placeholder('Tidak ada'),
                ]),

            Section::make('Nilai Sebelum')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    KeyValueEntry::make('old')
                        ->label('Fields')
                        ->columnSpanFull()
                        ->state(fn (Activity $record): array => $record->attribute_changes?->get('old', []) ?? [])
                        ->keyLabel('Field')
                        ->valueLabel('Nilai Sebelum')
                        ->placeholder('Tidak ada'),
                ]),
        ]);
    }
}
