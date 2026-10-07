<?php

namespace App\Filament\Resources\MsClients\RelationManagers;

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
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Dokumen Klien';

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
                    ->directory(fn (): string => 'documents/client/'.$this->getOwnerRecord()->getKey())
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->preventFilePathTampering()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Dokumen')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Diunggah Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
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
                    ->url(fn (MsDocument $record): string => Storage::disk('local')
                        ->temporaryUrl($record->file_path, now()->addMinutes(5)))
                    ->openUrlInNewTab()
                    ->visible(fn (MsDocument $record): bool => $this->hasFileWithMimeType($record, 'application/pdf')),
                Action::make('download')
                    ->label('Unduh')
                    ->action(function (MsDocument $record) {
                        abort_unless($this->hasClientFile($record), 404);

                        return Storage::disk('local')->download($record->file_path);
                    })
                    ->visible(fn (MsDocument $record): bool => $this->hasClientFile($record)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function hasClientFile(MsDocument $record): bool
    {
        return $record->ref_type === 'client'
            && $record->ref_id === $this->getOwnerRecord()->getKey()
            && dirname($record->file_path) === 'documents/client/'.$record->ref_id
            && Storage::disk('local')->exists($record->file_path);
    }

    private function hasFileWithMimeType(MsDocument $record, string $mimeType): bool
    {
        return $this->hasClientFile($record)
            && str_starts_with((string) Storage::disk('local')->mimeType($record->file_path), $mimeType);
    }
}
