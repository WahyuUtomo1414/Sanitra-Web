<?php

namespace App\Filament\Widgets;

use App\Enums\Status\ProjectStageStatus;
use App\Enums\Status\ProjectStatus;
use App\Models\TransProjectDt;
use App\Models\TransProjectHd;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeknisStatsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Project';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Teknis', 'super_admin']) ?? false;
    }

    protected function getStats(): array
    {
        $activeProjects = TransProjectHd::whereRelation('status', 'code', ProjectStatus::OnProgress->value)->count();
        $blockedStages = TransProjectDt::whereRelation('status', 'code', ProjectStageStatus::Blocked->value)->count();
        $avgProgress = (int) round(
            TransProjectHd::whereRelation('status', 'code', ProjectStatus::OnProgress->value)->avg('progress_percentage') ?? 0
        );
        $completedThisMonth = TransProjectHd::whereRelation('status', 'code', ProjectStatus::Completed->value)
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        return [
            Stat::make('Project Aktif', $activeProjects)
                ->color('info'),

            Stat::make('Tahap Blocked', $blockedStages)
                ->color($blockedStages > 0 ? 'danger' : 'success'),

            Stat::make('Rata-rata Progress', $avgProgress.'%')
                ->color('primary'),

            Stat::make('Selesai Bulan Ini', $completedThisMonth)
                ->color('success'),
        ];
    }
}
