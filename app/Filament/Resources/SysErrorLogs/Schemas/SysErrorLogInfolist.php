<?php

namespace App\Filament\Resources\SysErrorLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SysErrorLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Error')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('level')
                        ->label('Level')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'critical' => 'danger',
                            'warning' => 'warning',
                            default => 'danger',
                        }),
                    TextEntry::make('created_at')
                        ->label('Waktu')
                        ->dateTime('d M Y H:i'),
                    TextEntry::make('message')
                        ->label('Pesan')
                        ->columnSpanFull(),
                    TextEntry::make('url')
                        ->label('URL')
                        ->placeholder('-'),
                    TextEntry::make('method')
                        ->label('Method')
                        ->placeholder('-'),
                    TextEntry::make('user.name')
                        ->label('User')
                        ->placeholder('Sistem/Console'),
                    TextEntry::make('ip_address')
                        ->label('IP Address')
                        ->placeholder('-'),
                    TextEntry::make('context.exception')
                        ->label('Exception Class')
                        ->placeholder('-'),
                    TextEntry::make('context.file')
                        ->label('File')
                        ->placeholder('-')
                        ->formatStateUsing(fn (?string $state, $record): string => $state
                            ? ($state.' : '.($record->context['line'] ?? '?'))
                            : '-'),
                ]),

            Section::make('Stack Trace')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('trace')
                        ->label('')
                        ->columnSpanFull()
                        ->state(function (mixed $record): string {
                            $frames = $record->trace;

                            if (empty($frames)) {
                                return 'Tidak ada stack trace.';
                            }

                            $base = base_path().DIRECTORY_SEPARATOR;

                            return collect($frames)
                                ->map(function (array $frame, int $i) use ($base): string {
                                    $file = str_replace($base, '', $frame['file'] ?? '?');
                                    $location = $file.':'.($frame['line'] ?? '?');

                                    return sprintf('#%d %s  →  %s', $i, $frame['call'] ?? '?', $location);
                                })
                                ->implode("\n");
                        })
                        ->extraAttributes(['class' => 'font-mono text-xs whitespace-pre-wrap break-all']),
                ]),
        ]);
    }
}
