<?php

namespace Database\Factories;

use App\Models\MsStatus;
use App\Models\TransProjectDt;
use App\Models\TransProjectHd;
use App\Models\TransProjectMonitoring;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransProjectMonitoring>
 */
class TransProjectMonitoringFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_hd_id' => TransProjectHd::factory(),
            'project_dt_id' => TransProjectDt::factory(),
            'status_before_id' => MsStatus::query()->where('type', 'project_dt')->where('code', 'draft')->firstOrFail()->id,
            'status_after_id' => MsStatus::query()->where('type', 'project_dt')->where('code', 'on_progress')->firstOrFail()->id,
            'log_type' => 'status_change',
            'progress_before' => 0,
            'progress_after' => 0,
            'created_by' => User::query()->firstOrFail()->id,
        ];
    }
}
