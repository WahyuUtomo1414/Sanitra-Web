<?php

namespace App\Filament\Resources\TransSalesQuotationHds\RelationManagers;

use App\Enums\Status\QuotationStatus;
use App\Filament\Forms\Components\MoneyInput;
use App\Models\MsItem;
use App\Models\MsItemUnit;
use App\Models\TransSalesQuotationDt;
use App\Services\Quotation\ManageQuotationItemsService;
use App\Support\Formatting\IndonesianMoneyFormatter;
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

    protected static ?string $title = 'Item';

    protected static ?string $modelLabel = 'item';

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
            TextInput::make('qty')->label('Jumlah')->integer()->minValue(1)->required()->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
            MoneyInput::make('unit_price')->label('Harga Satuan')
                ->minValue(0)->required()->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
            MoneyInput::make('discount')->label('Diskon Baris')
                ->minValue(0)->nullable()->live()
                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotal($get, $set)),
            MoneyInput::display('total_price')->label('Total')->readOnly()->dehydrated(false),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
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
                TextColumn::make('unit_price')->label('Harga Satuan')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextColumn::make('discount')->label('Diskon')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextColumn::make('total_price')->label('Total')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
            ])
            ->headerActions([
                CreateAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (array $data): TransSalesQuotationDt => app(ManageQuotationItemsService::class)
                        ->save($this->getOwnerRecord(), $data))
                    ->after(fn (ItemsRelationManager $livewire) => $livewire->dispatch('quotation-totals-updated')),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (TransSalesQuotationDt $record, array $data): TransSalesQuotationDt => app(ManageQuotationItemsService::class)
                        ->save($this->getOwnerRecord(), $data, $record))
                    ->after(fn (ItemsRelationManager $livewire) => $livewire->dispatch('quotation-totals-updated')),
                DeleteAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (TransSalesQuotationDt $record): bool => app(ManageQuotationItemsService::class)
                        ->delete($this->getOwnerRecord(), $record))
                    ->after(fn (ItemsRelationManager $livewire) => $livewire->dispatch('quotation-totals-updated')),
            ]);
    }

    private function isDraft(): bool
    {
        return $this->getOwnerRecord()->fresh()->status?->code === QuotationStatus::Draft->value;
    }

    private static function updateTotal(Get $get, Set $set): void
    {
        $quantity = preg_replace('/\D/', '', (string) ($get('qty') ?: '0')) ?: '0';
        $unitPrice = IndonesianMoneyFormatter::parseInput($get('unit_price'));
        $discount = IndonesianMoneyFormatter::parseInput($get('discount'));
        $gross = bcmul($quantity, $unitPrice, 6);
        $total = bccomp($gross, $discount, 6) < 0 ? '0.000000' : bcsub($gross, $discount, 6);

        $set('total_price', IndonesianMoneyFormatter::formatNumber($total));
    }
}
