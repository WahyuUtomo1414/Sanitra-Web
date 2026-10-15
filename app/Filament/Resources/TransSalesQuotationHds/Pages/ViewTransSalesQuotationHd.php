<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Pages;

use App\Enums\Status\QuotationStatus;
use App\Filament\Resources\TransSalesQuotationHds\TransSalesQuotationHdResource;
use App\Services\Quotation\ApproveQuotationService;
use App\Services\Quotation\QuotationPdfDataService;
use App\Services\Quotation\RejectQuotationService;
use App\Services\Quotation\ReviseQuotationService;
use App\Services\Quotation\SendQuotationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTransSalesQuotationHd extends ViewRecord
{
    protected static string $resource = TransSalesQuotationHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah Detail')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->outlined()
                ->visible(fn(): bool => $this->record->status?->code === QuotationStatus::Draft->value),
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->outlined()
                ->visible(fn(): bool => $this->record->status?->code === QuotationStatus::Draft->value)
                ->action(fn() => response()->streamDownload(
                    fn() => print(Pdf::loadView('pdf.quotation', app(QuotationPdfDataService::class)->execute($this->record))->setPaper('a4', 'portrait')->output()),
                    str_replace('/', '-', $this->record->code) . '.pdf',
                )),
            Action::make('send')
                ->label('Kirim Penawaran')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn(): bool => $this->record->status?->code === QuotationStatus::Draft->value)
                ->requiresConfirmation()
                ->modalHeading('Kirim penawaran?')
                ->modalDescription('Status akan berubah menjadi Dikirim. Detail penawaran tidak dapat diedit sampai dikembalikan ke Draft.')
                ->modalSubmitActionLabel('Ya, kirim')
                ->action(fn() => app(SendQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation dikirim.')
                ->successRedirectUrl(fn(): string => static::getResource()::getUrl('view', ['record' => $this->record])),
            Action::make('approve')
                ->label('Setujui Penawaran')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(): bool => $this->record->status?->code === QuotationStatus::Sent->value)
                ->requiresConfirmation()
                ->modalHeading('Setujui penawaran?')
                ->modalDescription('Sales Order akan dibuat dan quotation akan terkunci.')
                ->modalSubmitActionLabel('Ya, setujui')
                ->action(fn() => app(ApproveQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation disetujui dan Sales Order dibuat.')
                ->successRedirectUrl(fn(): string => static::getResource()::getUrl('view', ['record' => $this->record])),
            Action::make('revise')
                ->label('Kembalikan ke Draft')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->outlined()
                ->visible(fn(): bool => $this->record->status?->code === QuotationStatus::Sent->value)
                ->requiresConfirmation()
                ->modalHeading('Kembalikan ke Draft?')
                ->modalDescription('Penawaran dapat diedit kembali setelah status berubah menjadi Draft.')
                ->modalSubmitActionLabel('Ya, revisi')
                ->action(fn() => app(ReviseQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation kembali ke Draft.')
                ->successRedirectUrl(fn(): string => static::getResource()::getUrl('view', ['record' => $this->record])),
            Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->outlined()
                ->visible(fn(): bool => $this->record->status?->code === QuotationStatus::Sent->value)
                ->requiresConfirmation()
                ->modalHeading('Tolak penawaran?')
                ->modalDescription('Status penawaran akan berubah menjadi Ditolak.')
                ->modalSubmitActionLabel('Ya, tolak')
                ->action(fn() => app(RejectQuotationService::class)->execute($this->record))
                ->successNotificationTitle('Quotation ditolak.')
                ->successRedirectUrl(fn(): string => static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}
