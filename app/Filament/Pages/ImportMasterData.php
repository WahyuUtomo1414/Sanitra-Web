<?php

namespace App\Filament\Pages;

use App\Imports\MsBankAccountImport;
use App\Imports\MsClientImport;
use App\Imports\MsCompanyImport;
use App\Imports\MsEmployeeImport;
use App\Imports\MsItemCategoryImport;
use App\Imports\MsItemImport;
use App\Models\ImportLog;
use BackedEnum;
use Filament\Actions\Action as FilamentAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class ImportMasterData extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $title = 'Import Data Master';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.import-master-data';

    public array $data = [];

    /** @var array{entity: string, success_count: int, errors: list<string>, status: string}|null */
    public ?array $importResult = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Import Data')
                    ->description('Pilih entitas, unduh template, isi data, lalu upload file Excel (.xlsx).')
                    ->columns(2)
                    ->schema([
                        Select::make('entity')
                            ->label('Entitas Master')
                            ->options([
                                'company' => 'Perusahaan',
                                'employee' => 'Karyawan',
                                'client' => 'Client',
                                'item_category' => 'Kategori Item',
                                'item' => 'Item',
                                'bank_account' => 'Rekening Bank',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->importResult = null),

                        Actions::make([
                            FilamentAction::make('downloadTemplate')
                                ->label('Unduh Template')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('gray')
                                ->outlined()
                                ->url(
                                    fn (Get $get): ?string => $get('entity')
                                        ? route('import.template', ['entity' => $get('entity')])
                                        : null
                                )
                                ->openUrlInNewTab()
                                ->disabled(fn (Get $get): bool => ! $get('entity')),
                        ])->verticalAlignment(VerticalAlignment::End),

                        FileUpload::make('file')
                            ->label('File Excel (.xlsx)')
                            ->columnSpanFull()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->disk('local')
                            ->directory('imports/tmp')
                            ->maxSize(5120)
                            ->required(),

                        Actions::make([
                            FilamentAction::make('submitImport')
                                ->label('Import Data')
                                ->icon('heroicon-o-arrow-up-tray')
                                ->action(fn () => $this->import()),
                        ])->columnSpanFull(),
                    ]),
            ]);
    }

    public function import(): void
    {
        $state = $this->form->getState();
        $entity = $state['entity'];
        $filePath = $state['file'];

        $importer = match ($entity) {
            'company' => new MsCompanyImport,
            'employee' => new MsEmployeeImport,
            'client' => new MsClientImport,
            'item_category' => new MsItemCategoryImport,
            'item' => new MsItemImport,
            'bank_account' => new MsBankAccountImport,
        };

        Excel::import($importer, $filePath, 'local');

        $totalRows = $importer->successCount + count($importer->errors);
        $status = match (true) {
            count($importer->errors) === 0 => 'done',
            $importer->successCount === 0 => 'failed',
            default => 'partial',
        };

        ImportLog::create([
            'imported_by' => Auth::id(),
            'entity' => $entity,
            'filename' => basename($filePath),
            'total_rows' => $totalRows,
            'success_count' => $importer->successCount,
            'failed_count' => count($importer->errors),
            'errors' => count($importer->errors) > 0 ? $importer->errors : null,
            'status' => $status,
        ]);

        Storage::disk('local')->delete($filePath);

        $this->importResult = [
            'entity' => $entity,
            'success_count' => $importer->successCount,
            'errors' => $importer->errors,
            'status' => $status,
        ];

        $this->form->fill(['entity' => $entity]);

        match ($status) {
            'done' => Notification::make()
                ->title($importer->successCount.' data berhasil diimport.')
                ->success()
                ->send(),
            'partial' => Notification::make()
                ->title($importer->successCount.' data berhasil, '.count($importer->errors).' baris gagal.')
                ->warning()
                ->send(),
            'failed' => Notification::make()
                ->title('Import gagal. Semua baris mengandung kesalahan.')
                ->danger()
                ->send(),
        };
    }
}
