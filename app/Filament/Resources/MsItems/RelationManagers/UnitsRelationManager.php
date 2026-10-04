<?php

namespace App\Filament\Resources\MsItems\RelationManagers;

use App\Models\MsUnit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                ->options(fn () => MsUnit::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),

            Toggle::make('is_base')
                ->label('Unit Dasar')
                ->helperText('Setiap item wajib memiliki tepat satu unit dasar.')
                ->default(false),

            TextInput::make('conversion')
                ->label('Faktor Konversi')
                ->integer()
                ->minValue(1)
                ->default(1)
                ->required()
                ->helperText('Konversi terhadap unit dasar (unit dasar = 1).'),

            Toggle::make('active')
                ->label('Aktif')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('unit.name')
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
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
