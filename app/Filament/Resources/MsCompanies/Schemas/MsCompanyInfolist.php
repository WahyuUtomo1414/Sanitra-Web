<?php

namespace App\Filament\Resources\MsCompanies\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MsCompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Utama')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('code')->label('Kode')->columnSpanFull(),
                    TextEntry::make('name')->label('Nama'),
                    TextEntry::make('legal_name')->label('Nama Legal'),
                    TextEntry::make('email')->label('Email'),
                    TextEntry::make('phone')->label('Telepon'),
                    TextEntry::make('description')->label('Deskripsi')->placeholder('-')->columnSpanFull(),
                    IconEntry::make('active')->label('Aktif')->boolean(),
                ]),

            Section::make('Relasi')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextEntry::make('parent.name')->label('Perusahaan Induk')->placeholder('-'),
                    TextEntry::make('picEmployee.name')->label('PIC Karyawan')->placeholder('-'),
                ]),

            Section::make('Audit Data')
                ->columnSpanFull()
                ->columns(2)
                ->collapsible()
                ->schema([
                    TextEntry::make('createdBy.name')->label('Dibuat Oleh')->placeholder('-'),
                    TextEntry::make('created_at')->label('Dibuat Pada')->dateTime('d M Y H:i'),
                    TextEntry::make('updatedBy.name')->label('Diubah Oleh')->placeholder('-'),
                    TextEntry::make('updated_at')->label('Diubah Pada')->dateTime('d M Y H:i')->placeholder('-'),
                    TextEntry::make('deletedBy.name')->label('Dihapus Oleh')->placeholder('-')->visible(fn ($record) => $record?->deleted_at),
                    TextEntry::make('deleted_at')->label('Dihapus Pada')->dateTime('d M Y H:i')->visible(fn ($record) => $record?->deleted_at),
                ]),
        ]);
    }
}
