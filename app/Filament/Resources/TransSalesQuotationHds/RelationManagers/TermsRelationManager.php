<?php

namespace App\Filament\Resources\TransSalesQuotationHds\RelationManagers;

use App\Enums\Status\QuotationStatus;
use App\Models\TransSalesTerm;
use App\Services\Quotation\ManageQuotationTermsService;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    protected static ?string $title = 'Skema Termin';

    protected static ?string $modelLabel = 'skema termin';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('term_name')->label('Nama Termin')->required()->maxLength(128),
            TextInput::make('percentage')->label('Persentase')->integer()->minValue(1)->maxValue(100)->required()->suffix('%'),
            TextInput::make('progress_threshold')->label('Threshold Progres')
                ->integer()->minValue(0)->maxValue(100)->required()->suffix('%')
                ->helperText('Isi 0 untuk termin DP. Tepat satu DP diperlukan sebelum dikirim.'),
            Textarea::make('notes')->label('Catatan'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('term_name')
            ->columns([
                TextColumn::make('sequence')->label('Urutan')->sortable(),
                TextColumn::make('term_name')->label('Termin')->searchable(),
                TextColumn::make('percentage')->label('Persentase')->suffix('%'),
                TextColumn::make('progress_threshold')->label('Threshold')->suffix('%'),
                TextColumn::make('amount')->label('Nilai')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextColumn::make('status.name')->label('Status')->badge(),
            ])
            ->headerActions([
                CreateAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (array $data): TransSalesTerm => app(ManageQuotationTermsService::class)
                        ->save($this->getOwnerRecord(), $data))
                    ->after(fn (TermsRelationManager $livewire) => $livewire->dispatch('refresh-page')),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (TransSalesTerm $record, array $data): TransSalesTerm => app(ManageQuotationTermsService::class)
                        ->save($this->getOwnerRecord(), $data, $record))
                    ->after(fn (TermsRelationManager $livewire) => $livewire->dispatch('refresh-page')),
                DeleteAction::make()->visible(fn (): bool => $this->isDraft())
                    ->using(fn (TransSalesTerm $record): bool => app(ManageQuotationTermsService::class)
                        ->delete($this->getOwnerRecord(), $record))
                    ->after(fn (TermsRelationManager $livewire) => $livewire->dispatch('refresh-page')),
            ]);
    }

    private function isDraft(): bool
    {
        return $this->getOwnerRecord()->fresh()->status?->code === QuotationStatus::Draft->value;
    }
}
