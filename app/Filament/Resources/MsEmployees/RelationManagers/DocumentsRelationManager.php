<?php

namespace App\Filament\Resources\MsEmployees\RelationManagers;

use App\Models\MsDocument;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Dokumen Karyawan';

    /** Menyusun formulir unggah dokumen karyawan. */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Dokumen')
                    ->required()
                    ->maxLength(128),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3),

                FileUpload::make('file_path')
                    ->label('File')
                    ->disk('local')
                    ->directory(fn (): string => 'documents/employee/'.$this->getOwnerRecord()->getKey())
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->preventFilePathTampering()
                    ->required(),
            ]);
    }

    /** Menyusun daftar dokumen dan aksi untuk melihat, mengunduh, atau mengubahnya. */
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modelLabel('dokumen')
            ->pluralModelLabel('dokumen')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Dokumen')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Diunggah Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Dokumen'),
            ])
            ->recordActions([
                ViewAction::make('viewImage')
                    ->label('Lihat Gambar')
                    ->modalHeading(fn (MsDocument $record): string => $record->name)
                    ->schema([
                        ImageEntry::make('file_path')
                            ->label('Gambar')
                            ->disk('local')
                            ->visibility('private')
                            ->imageWidth('100%')
                            ->imageHeight('auto')
                            ->alt(fn (MsDocument $record): string => $record->name)
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->modalWidth('5xl')
                    ->visible(fn (MsDocument $record): bool => $this->hasFileWithMimeType($record, 'image/')),
                Action::make('viewPdf')
                    ->label('Lihat PDF')
                    ->url(function (MsDocument $record): string {
                        /** @var FilesystemAdapter $disk */
                        $disk = Storage::disk('local');

                        return $disk->temporaryUrl($record->file_path, now()->addMinutes(5));
                    })
                    ->openUrlInNewTab()
                    ->visible(fn (MsDocument $record): bool => $this->hasFileWithMimeType($record, 'application/pdf')),
                Action::make('download')
                    ->label('Unduh')
                    ->action(function (MsDocument $record) {
                        abort_unless($this->hasEmployeeFile($record), 404);

                        /** @var FilesystemAdapter $disk */
                        $disk = Storage::disk('local');

                        return $disk->download($record->file_path);
                    })
                    ->visible(fn (MsDocument $record): bool => $this->hasEmployeeFile($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Memastikan file tersimpan dan benar-benar milik karyawan ini. */
    private function hasEmployeeFile(MsDocument $record): bool
    {
        return $record->ref_type === 'employee'
            && $record->ref_id === $this->getOwnerRecord()->getKey()
            && dirname($record->file_path) === 'documents/employee/'.$record->ref_id
            && Storage::disk('local')->exists($record->file_path);
    }

    /** Memastikan file milik karyawan ini sesuai dengan jenis media yang diminta. */
    private function hasFileWithMimeType(MsDocument $record, string $mimeType): bool
    {
        return $this->hasEmployeeFile($record)
            && str_starts_with((string) Storage::disk('local')->mimeType($record->file_path), $mimeType);
    }
}
