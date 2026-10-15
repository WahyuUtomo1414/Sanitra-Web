<?php

namespace App\Filament\Resources\TransSalesOrderHds\Pages;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListTransSalesOrderHds extends ListRecords
{
    protected static string $resource = TransSalesOrderHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()->label('Unduh Excel')->icon('heroicon-o-arrow-down-tray')
                ->button()->outlined()->color('success')->size(Size::Medium)
                ->exports([
                    ExcelExport::make()->fromTable()
                        ->withFilename('sales-orders-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
