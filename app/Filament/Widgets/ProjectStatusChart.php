<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class ProjectStatusChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Status Project (Data Contoh)';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Project',
                    'data' => [12, 3, 8, 2],
                    'backgroundColor' => ['#16a34a', '#f59e0b', '#64748b', '#ef4444'],
                ],
            ],
            'labels' => ['Aktif', 'Ditunda', 'Selesai', 'Dibatalkan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
