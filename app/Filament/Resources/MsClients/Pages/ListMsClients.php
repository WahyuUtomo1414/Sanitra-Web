<?php

namespace App\Filament\Resources\MsClients\Pages;

use App\Filament\Resources\MsClients\MsClientResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListMsClients extends ListRecords
{
    protected static string $resource = MsClientResource::class;

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
                        ->withFilename('msclients-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
