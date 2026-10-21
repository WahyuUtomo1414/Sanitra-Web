<?php

namespace App\Filament\Widgets;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Enums\Status\TermStatus;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesPayment;
use App\Models\TransSalesTerm;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Keuangan';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Finance', 'super_admin']) ?? false;
    }

    protected function getStats(): array
    {
        $unpaidCount = TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Unpaid->value)->count();
        $totalOutstanding = (float) TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Unpaid->value)->sum('grand_total');
        $overdueCount = TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Unpaid->value)->where('due_date', '<', today())->count();
        $rtbCount = TransSalesTerm::whereRelation('status', 'code', TermStatus::ReadyToBill->value)->count();
        $pendingCount = TransSalesPayment::whereRelation('status', 'code', PaymentStatus::Pending->value)->count();
        $revenueThisMonth = (float) TransSalesPayment::whereRelation('status', 'code', PaymentStatus::Confirmed->value)
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount');

        return [
            Stat::make('Invoice Outstanding', $unpaidCount)
                ->color('warning'),

            Stat::make('Total Outstanding', 'Rp '.number_format($totalOutstanding, 0, ',', '.'))
                ->color('warning'),

            Stat::make('Invoice Jatuh Tempo', $overdueCount)
                ->color($overdueCount > 0 ? 'danger' : 'success'),

            Stat::make('Termin Siap Tagih', $rtbCount)
                ->color($rtbCount > 0 ? 'info' : 'gray'),

            Stat::make('Pembayaran Pending', $pendingCount)
                ->color($pendingCount > 0 ? 'warning' : 'gray'),

            Stat::make('Revenue Bulan Ini', 'Rp '.number_format($revenueThisMonth, 0, ',', '.'))
                ->color('success'),
        ];
    }
}
