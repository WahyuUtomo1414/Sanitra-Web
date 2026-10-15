<?php

namespace App\Filament\Resources\TransSalesOrderHds\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransSalesOrderHdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Catatan Sales Order')
                ->description('Item, nilai, dan status berasal dari proses penjualan dan tidak dapat diubah di sini.')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('notes')->label('Catatan')->nullable()->rows(5),
                ]),
        ]);
    }
}
