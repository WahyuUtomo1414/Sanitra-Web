<?php

namespace App\Filament\Widgets;

use App\Enums\Status\QuotationStatus;
use App\Models\TransSalesQuotationHd;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MarketingConversionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Konversi Quotation — 6 Bulan Terakhir';

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Marketing', 'super_admin']) ?? false;
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn (int $i): Carbon => now()->subMonths($i));

        return [
            'labels' => $months->map(fn (Carbon $m): string => $m->format('M Y'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Dikirim',
                    'data' => $months->map(fn (Carbon $m): int => TransSalesQuotationHd::whereRelation('status', 'code', QuotationStatus::Sent->value)
                        ->whereMonth('transaction_date', $m->month)
                        ->whereYear('transaction_date', $m->year)
                        ->count())->toArray(),
                    'backgroundColor' => 'rgba(251, 191, 36, 0.8)',
                ],
                [
                    'label' => 'Disetujui',
                    'data' => $months->map(fn (Carbon $m): int => TransSalesQuotationHd::whereRelation('status', 'code', QuotationStatus::Approved->value)
                        ->whereMonth('transaction_date', $m->month)
                        ->whereYear('transaction_date', $m->year)
                        ->count())->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
