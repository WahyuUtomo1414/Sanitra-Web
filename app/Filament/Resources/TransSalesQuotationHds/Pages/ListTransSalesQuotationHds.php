<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Pages;

use App\Filament\Resources\TransSalesQuotationHds\TransSalesQuotationHdResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListTransSalesQuotationHds extends ListRecords
{
    protected static string $resource = TransSalesQuotationHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()->label('Unduh Excel')->icon('heroicon-o-arrow-down-tray')
                ->button()->outlined()->color('success')->size(Size::Medium)
                ->exports([
                    ExcelExport::make()->fromTable()
                        ->withFilename('quotations-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
