<?php

namespace App\Filament\Widgets;

use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Models\MsItem;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OwnerStats extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $heading = 'Ringkasan Owner';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Perusahaan', MsCompany::count()),
            Stat::make('Total Karyawan', MsEmployee::count()),
            Stat::make('Total Klien', MsClient::count()),
            Stat::make('Total Item', MsItem::count()),
            Stat::make('Quotation Bulan Ini', 24)->description('Data contoh — transaksi belum tersedia'),
            Stat::make('Project Aktif', 12)->description('Data contoh — transaksi belum tersedia'),
            Stat::make('Invoice Outstanding', 8)->description('Data contoh — transaksi belum tersedia'),
            Stat::make('Revenue Bulan Ini', 'Rp 125.000.000')->description('Data contoh — transaksi belum tersedia'),
        ];
    }
}
