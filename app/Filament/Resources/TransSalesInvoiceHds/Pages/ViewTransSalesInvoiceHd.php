<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Pages;

use App\Filament\Resources\TransSalesInvoiceHds\TransSalesInvoiceHdResource;
use App\Services\Invoice\InvoicePdfDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewTransSalesInvoiceHd extends ViewRecord
{
    protected static string $resource = TransSalesInvoiceHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->outlined()
                ->action(function () {
                    $pdfData = app(InvoicePdfDataService::class)->execute($this->record);

                    return response()->streamDownload(
                        fn () => print (Pdf::loadView('pdf.invoice', $pdfData)->setPaper('a4', 'portrait')->output()),
                        'invoice-'.str_replace('/', '-', $this->record->code).'.pdf',
                    );
                }),
        ];
    }
}
