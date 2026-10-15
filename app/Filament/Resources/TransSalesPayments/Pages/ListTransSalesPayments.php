<?php

namespace App\Filament\Resources\TransSalesPayments\Pages;

use App\Filament\Resources\TransSalesPayments\TransSalesPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListTransSalesPayments extends ListRecords
{
    protected static string $resource = TransSalesPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()->label('Unduh Excel')->icon('heroicon-o-arrow-down-tray')
                ->button()->outlined()->color('success')->size(Size::Medium)
                ->exports([
                    ExcelExport::make()->fromTable()
                        ->withFilename('payments-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
