<?php

namespace App\Filament\Resources\TransSalesOrderHds\Pages;

use App\Enums\Status\OrderCloseReason;
use App\Enums\Status\OrderStatus;
use App\Enums\Status\TermStatus;
use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Services\SalesOrder\CloseOrderService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewTransSalesOrderHd extends ViewRecord
{
    protected static string $resource = TransSalesOrderHdResource::class;

    public function getSubheading(): ?string
    {
        if ($this->record->status?->code === OrderStatus::Open->value
            && ! $this->record->project()->exists()) {
            return 'DP lunas. Sales Order siap dibuatkan Project.';
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah Catatan')->color('gray')->outlined(),
            Action::make('createInvoice')
                ->label('Buat Invoice')
                ->icon('heroicon-o-document-plus')
                ->color('info')
                ->visible(fn (): bool => TransSalesOrderHdResource::canEdit($this->record) && $this->record->terms()
                    ->whereHas('status', fn ($query) => $query->where('code', TermStatus::ReadyToBill->value))
                    ->exists())
                ->url(fn (): string => static::getResource()::getUrl('createInvoice', ['record' => $this->record])),
            Action::make('createProject')
                ->label('Buat Project')
                ->icon('heroicon-o-building-office-2')
                ->color('success')
                ->visible(fn (): bool => TransSalesOrderHdResource::canEdit($this->record)
                    && $this->record->status?->code === OrderStatus::Open->value
                    && ! $this->record->project()->exists())
                ->url(fn (): string => static::getResource()::getUrl('createProject', ['record' => $this->record])),
            Action::make('closeOrder')
                ->label('Tutup SO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->outlined()
                ->visible(fn (): bool => $this->canClose())
                ->schema([
                    Select::make('close_reason')
                        ->label('Alasan Penutupan')
                        ->options(collect(OrderCloseReason::cases())->mapWithKeys(fn (OrderCloseReason $r): array => [$r->value => $r->label()]))
                        ->required(),
                    Textarea::make('close_notes')->label('Catatan Penutupan')->nullable()->rows(3),
                ])
                ->modalHeading('Tutup Sales Order')
                ->modalDescription('Pastikan tidak ada invoice yang belum dibayar. Project yang sedang berjalan akan dibatalkan.')
                ->action(function (array $data): void {
                    try {
                        app(CloseOrderService::class)->execute($this->record, $data['close_reason'], $data['close_notes'] ?? null);
                    } catch (DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }
                    Notification::make()->title('Sales Order berhasil ditutup.')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    private function canClose(): bool
    {
        return Gate::allows('update', $this->record)
            && in_array($this->record->status?->code, [OrderStatus::Draft->value, OrderStatus::Open->value]);
    }
}
