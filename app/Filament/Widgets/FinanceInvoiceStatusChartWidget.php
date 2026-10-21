<?php

namespace App\Filament\Widgets;

use App\Enums\Status\InvoiceStatus;
use App\Models\TransSalesInvoiceHd;
use Filament\Widgets\ChartWidget;

class FinanceInvoiceStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Status Invoice';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Finance', 'super_admin']) ?? false;
    }

    protected function getData(): array
    {
        return [
            'labels' => ['Belum Dibayar', 'Lunas', 'Void'],
            'datasets' => [
                [
                    'data' => [
                        TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Unpaid->value)->count(),
                        TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Paid->value)->count(),
                        TransSalesInvoiceHd::whereRelation('status', 'code', InvoiceStatus::Void->value)->count(),
                    ],
                    'backgroundColor' => [
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(156, 163, 175, 0.8)',
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
