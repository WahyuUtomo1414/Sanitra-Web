<?php

namespace App\Filament\Resources\TransSalesOrderHds\RelationManagers;

use App\Enums\Status\TermStatus;
use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransSalesTerm;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    protected static ?string $title = 'Skema Termin';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('notes')->label('Catatan')->nullable()->rows(4),
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
                TextColumn::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesTerm $record): string => TermStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
            ])
            ->recordActions([
                EditAction::make()->label('Ubah Catatan')
                    ->visible(fn (): bool => TransSalesOrderHdResource::canEdit($this->getOwnerRecord()))
                    ->using(function (TransSalesTerm $record, array $data): TransSalesTerm {
                        if ($record->order_hd_id !== $this->getOwnerRecord()->id) {
                            abort(403);
                        }

                        abort_unless(TransSalesOrderHdResource::canEdit($this->getOwnerRecord()), 403);

                        $record->update(['notes' => $data['notes'] ?? null]);

                        return $record;
                    }),
            ]);
    }
}
