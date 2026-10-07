<?php

namespace App\Filament\Resources\MsClients\RelationManagers;

use App\Models\MsClientContact;
use App\Services\Client\SaveClientContactService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Kontak Klien';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kontak')
                    ->required()
                    ->maxLength(128),
                TextInput::make('position')
                    ->label('Jabatan')
                    ->maxLength(128),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(128),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(18),
                Toggle::make('is_primary')
                    ->label('Kontak Utama')
                    ->helperText('Hanya satu kontak utama per klien.')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kontak')
                    ->searchable(),
                TextColumn::make('position')->label('Jabatan'),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('Telepon'),
                IconColumn::make('is_primary')->label('Kontak Utama')->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data): MsClientContact => app(SaveClientContactService::class)
                        ->create($this->getOwnerRecord(), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (MsClientContact $record, array $data): MsClientContact => app(SaveClientContactService::class)
                        ->update($record, $data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
