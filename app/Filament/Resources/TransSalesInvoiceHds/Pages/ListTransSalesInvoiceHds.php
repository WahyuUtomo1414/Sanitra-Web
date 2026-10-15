<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Pages;

use App\Filament\Resources\TransSalesInvoiceHds\TransSalesInvoiceHdResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListTransSalesInvoiceHds extends ListRecords
{
    protected static string $resource = TransSalesInvoiceHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()->label('Unduh Excel')->icon('heroicon-o-arrow-down-tray')
                ->button()->outlined()->color('success')->size(Size::Medium)
                ->exports([
                    ExcelExport::make()->fromTable()
                        ->withFilename('invoices-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
