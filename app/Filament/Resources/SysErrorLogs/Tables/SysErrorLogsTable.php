<?php

namespace App\Filament\Resources\SysErrorLogs\Tables;

use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SysErrorLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->formatStateUsing(fn (?string $state): string => $state ? parse_url($state, PHP_URL_PATH) ?? $state : '-')
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('Sistem/Console')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        'error' => 'Error',
                        'critical' => 'Critical',
                        'warning' => 'Warning',
                    ]),
                Filter::make('created_at')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn (): array => User::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
