<?php

namespace App\Filament\Resources\MsProjectWorkflows\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MsProjectWorkflowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sequence')
            ->columns([
                TextColumn::make('type.name')
                    ->label('Tipe Project')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sequence')
                    ->label('Urutan')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Tahap')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('progress_weight')
                    ->label('Bobot')
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make('active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->badge()
                    ->description(fn ($record) => $record->created_at?->format('d M Y H:i'))
                    ->sortable(),

                TextColumn::make('updatedBy.name')
                    ->label('Diubah Oleh')
                    ->badge()
                    ->description(fn ($record) => $record->updated_at?->format('d M Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deletedBy.name')
                    ->label('Dihapus Oleh')
                    ->badge()
                    ->description(fn ($record) => $record->deleted_at?->format('d M Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type_id')
                    ->label('Tipe Project')
                    ->relationship('type', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('active')
                    ->label('Status Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
