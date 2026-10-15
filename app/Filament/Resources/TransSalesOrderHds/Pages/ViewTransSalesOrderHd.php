<?php

namespace App\Filament\Resources\TransSalesOrderHds\Pages;

use App\Enums\Status\OrderStatus;
use App\Enums\Status\TermStatus;
use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Services\Project\CreateProjectFromSalesOrderService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransSalesOrderHd extends ViewRecord
{
    protected static string $resource = TransSalesOrderHdResource::class;

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
                    && $this->record->status?->code === OrderStatus::WaitingDp->value
                    && ! $this->record->project()->exists()
                    && app(CreateProjectFromSalesOrderService::class)->hasPaidDownPayment($this->record))
                ->url(fn (): string => static::getResource()::getUrl('createProject', ['record' => $this->record])),
        ];
    }
}
