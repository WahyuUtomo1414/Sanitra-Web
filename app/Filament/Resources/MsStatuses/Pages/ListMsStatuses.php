<?php

namespace App\Filament\Resources\MsStatuses\Pages;

use App\Filament\Resources\MsStatuses\MsStatusResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListMsStatuses extends ListRecords
{
    protected static string $resource = MsStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ExportAction::make()
                ->label('Unduh Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->button()
                ->outlined()
                ->color('success')
                ->size(Size::Medium)
                ->tooltip('Unduh data sesuai filter dalam format Excel (.xlsx)')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('msstatuses-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
