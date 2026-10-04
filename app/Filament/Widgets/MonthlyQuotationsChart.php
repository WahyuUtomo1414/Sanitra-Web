<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class MonthlyQuotationsChart extends ChartWidget
{
    protected static ?int $sort = 0;

    protected ?string $heading = 'Quotation per Bulan (Data Contoh)';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Quotation',
                    'data' => [7, 9, 6, 12, 10, 14, 11, 16, 13, 18, 20, 24],
                    'backgroundColor' => '#16a34a',
                ],
            ],
            'labels' => collect(range(11, 0))
                ->map(fn (int $monthsAgo): string => now()->subMonths($monthsAgo)->translatedFormat('M Y'))
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
