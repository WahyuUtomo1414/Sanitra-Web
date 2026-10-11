<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Pages;

use App\Enums\Status\QuotationStatus;
use App\Filament\Resources\TransSalesQuotationHds\TransSalesQuotationHdResource;
use App\Services\Quotation\ApproveQuotationService;
use App\Services\Quotation\RejectQuotationService;
use App\Services\Quotation\ReviseQuotationService;
use App\Services\Quotation\SendQuotationService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransSalesQuotationHd extends ViewRecord
{
    protected static string $resource = TransSalesQuotationHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => $this->record->status?->code === QuotationStatus::Draft->value),
            Action::make('send')->label('Kirim')->color('info')
                ->visible(fn (): bool => $this->record->status?->code === QuotationStatus::Draft->value)
                ->requiresConfirmation()
                ->action(fn () => app(SendQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation dikirim.'),
            Action::make('approve')->label('Setujui')->color('success')
                ->visible(fn (): bool => $this->record->status?->code === QuotationStatus::Sent->value)
                ->requiresConfirmation()
                ->modalDescription('Sales Order akan dibuat dan quotation terkunci.')
                ->action(fn () => app(ApproveQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation disetujui dan Sales Order dibuat.'),
            Action::make('reject')->label('Tolak')->color('danger')
                ->visible(fn (): bool => $this->record->status?->code === QuotationStatus::Sent->value)
                ->requiresConfirmation()
                ->action(fn () => app(RejectQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation ditolak.'),
            Action::make('revise')->label('Revisi')->color('warning')
                ->visible(fn (): bool => $this->record->status?->code === QuotationStatus::Sent->value)
                ->requiresConfirmation()
                ->action(fn () => app(ReviseQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation kembali ke Draft.'),
        ];
    }
}
