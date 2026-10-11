<?php

namespace Database\Factories;

use App\Models\MsProjectWorkflow;
use App\Models\MsStatus;
use App\Models\TransProjectDt;
use App\Models\TransProjectHd;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransProjectDt>
 */
class TransProjectDtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_hd_id' => TransProjectHd::factory(),
            'project_workflow_id' => MsProjectWorkflow::query()->firstOrFail()->id,
            'sequence' => 1,
            'work_name' => 'Tahap Contoh',
            'progress_weight' => 100,
            'status_id' => MsStatus::query()->where('type', 'project_dt')->where('code', 'draft')->firstOrFail()->id,
            'editable' => true,
        ];
    }
}
