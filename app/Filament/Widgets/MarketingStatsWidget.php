<?php

namespace App\Filament\Widgets;

use App\Enums\Status\OrderStatus;
use App\Enums\Status\QuotationStatus;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesQuotationHd;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Penjualan';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Marketing', 'super_admin']) ?? false;
    }

    protected function getStats(): array
    {
        $draft = TransSalesQuotationHd::whereRelation('status', 'code', QuotationStatus::Draft->value)->count();
        $sent = TransSalesQuotationHd::whereRelation('status', 'code', QuotationStatus::Sent->value)->count();
        $approved = TransSalesQuotationHd::whereRelation('status', 'code', QuotationStatus::Approved->value)->count();
        $pipeline = (float) TransSalesOrderHd::whereRelation('status', 'code', OrderStatus::Open->value)->sum('grand_total');

        return [
            Stat::make('Quotation Draft', $draft)
                ->color('gray'),

            Stat::make('Quotation Dikirim', $sent)
                ->color('warning'),

            Stat::make('Quotation Disetujui', $approved)
                ->color('success'),

            Stat::make('Nilai Pipeline SO Aktif', 'Rp '.number_format($pipeline, 0, ',', '.'))
                ->color('info'),
        ];
    }
}
