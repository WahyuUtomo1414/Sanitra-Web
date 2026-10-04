<?php

namespace App\Filament\Resources\MsBankAccounts\Pages;

use App\Filament\Resources\MsBankAccounts\MsBankAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListMsBankAccounts extends ListRecords
{
    protected static string $resource = MsBankAccountResource::class;

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
                        ->withFilename('msbankaccounts-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
