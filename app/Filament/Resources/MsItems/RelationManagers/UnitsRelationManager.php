<?php

namespace App\Filament\Resources\MsItems\RelationManagers;

use App\Models\MsItemUnit;
use App\Models\MsUnit;
use App\Services\Item\ManageItemUnitsService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    protected static ?string $title = 'Unit Item';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')
                ->label('Unit')
                ->options(fn () => MsUnit::query()
                    ->where('active', true)
                    ->whereNotIn('id', $this->getOwnerRecord()->units()->pluck('unit_id'))
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('conversion')
                ->label('Konversi')
                ->integer()
                ->minValue(1)
                ->default(1)
                ->required()
                ->readOnly(fn (): bool => ! $this->getOwnerRecord()->units()->where('is_base', true)->exists())
                ->helperText('Unit dasar selalu 1. Untuk unit tambahan, isi nilainya sesuai kebutuhan.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('unit.name')
            ->modelLabel('unit item')
            ->pluralModelLabel('unit item')
            ->columns([
                TextColumn::make('unit.name')->label('Unit')->sortable()->searchable(),
                IconColumn::make('is_base')->label('Unit Dasar')->boolean()->sortable(),
                TextColumn::make('conversion')->label('Konversi')->sortable(),
                IconColumn::make('active')->label('Aktif')->boolean()->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->badge()
                    ->description(fn ($record) => $record->created_at?->format('d M Y H:i'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Unit')
                    ->using(fn (array $data): MsItemUnit => app(ManageItemUnitsService::class)
                        ->add($this->getOwnerRecord(), MsUnit::query()->findOrFail($data['unit_id']), (int) $data['conversion'])),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema([
                        TextInput::make('conversion')
                            ->label('Konversi')
                            ->integer()
                            ->minValue(1)
                            ->readOnly(fn (MsItemUnit $record): bool => $record->is_base)
                            ->required(),
                    ])
                    ->using(fn (MsItemUnit $record, array $data): MsItemUnit => app(ManageItemUnitsService::class)
                        ->updateConversion($record, (int) $data['conversion'])),
                DeleteAction::make()
                    ->using(fn (MsItemUnit $record): bool => app(ManageItemUnitsService::class)->remove($record))
                    ->visible(fn (MsItemUnit $record): bool => ! $record->is_base),
            ]);
    }
}
