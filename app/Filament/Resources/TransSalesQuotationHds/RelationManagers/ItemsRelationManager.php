<?php

namespace App\Filament\Resources\TransSalesQuotationHds\RelationManagers;

use App\Enums\Status\QuotationStatus;
use App\Models\MsItem;
use App\Models\MsItemUnit;
use App\Models\TransSalesQuotationDt;
use App\Services\Quotation\ManageQuotationItemsService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Quotation';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('item_id')->label('Item')
                ->options(fn () => MsItem::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                ->searchable()->preload()->required()->live()
                ->afterStateUpdated(fn (Set $set) => $set('unit_id', null)),
            Select::make('unit_id')->label('Unit')
                ->options(fn (Get $get) => MsItemUnit::query()->where('item_id', $get('item_id'))
                    ->where('active', true)->with('unit')->get()
                    ->pluck('unit.name', 'unit_id'))
                ->searchable()->preload()->required(),
            TextInput::make('qty')->label('Jumlah')->integer()->minValue(1)->required(),
            TextInput::make('unit_price')->label('Harga Satuan')->numeric()->minValue(0)->required()->prefix('Rp'),
            TextInput::make('discount')->label('Diskon Baris')->numeric()->minValue(0)->default(0)->required()->prefix('Rp'),
            Textarea::make('notes')->label('Catatan'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.name')
            ->columns([
                TextColumn::make('item.name')->label('Item')->searchable(),
                TextColumn::make('unit.name')->label('Unit'),
                TextColumn::make('qty')->label('Jumlah'),
                TextColumn::make('unit_price')->label('Harga Satuan')->money('IDR'),
                TextColumn::make('discount')->label('Diskon')->money('IDR'),
                TextColumn::make('total_price')->label('Total')->money('IDR'),
            ])
            ->headerActions([
                CreateAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (array $data): TransSalesQuotationDt => app(ManageQuotationItemsService::class)
                        ->save($this->getOwnerRecord(), $data)),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (TransSalesQuotationDt $record, array $data): TransSalesQuotationDt => app(ManageQuotationItemsService::class)
                        ->save($this->getOwnerRecord(), $data, $record)),
                DeleteAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (TransSalesQuotationDt $record): bool => app(ManageQuotationItemsService::class)
                        ->delete($this->getOwnerRecord(), $record)),
            ]);
    }

    private function isDraft(): bool
    {
        return $this->getOwnerRecord()->fresh()->status?->code === QuotationStatus::Draft->value;
    }
}
