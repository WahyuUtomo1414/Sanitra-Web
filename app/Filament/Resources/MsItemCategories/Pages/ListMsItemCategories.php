<?php

namespace App\Filament\Resources\MsItemCategories\Pages;

use App\Filament\Resources\MsItemCategories\MsItemCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Size;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListMsItemCategories extends ListRecords
{
    protected static string $resource = MsItemCategoryResource::class;

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
                        ->withFilename('msitemcategories-'.now()->format('Ymd'))
                        ->withWriterType(Excel::XLSX),
                ]),
        ];
    }
}
