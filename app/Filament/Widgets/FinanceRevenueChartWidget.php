<?php

namespace App\Filament\Widgets;

use App\Enums\Status\PaymentStatus;
use App\Models\TransSalesPayment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinanceRevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue Masuk — 6 Bulan Terakhir';

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Finance', 'super_admin']) ?? false;
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i): Carbon => now()->subMonths($i));

        return [
            'labels' => $months->map(fn (Carbon $m): string => $m->format('M Y'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Revenue (Rp)',
                    'data' => $months->map(fn (Carbon $m): float => (float) TransSalesPayment::whereRelation('status', 'code', PaymentStatus::Confirmed->value)
                        ->whereMonth('transaction_date', $m->month)
                        ->whereYear('transaction_date', $m->year)
                        ->sum('amount'))->toArray(),
                    'backgroundColor' => 'rgba(22, 101, 52, 0.8)',
                    'borderColor' => 'rgb(22, 101, 52)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
