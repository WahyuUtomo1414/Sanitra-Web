<?php

namespace App\Services\Project;

use App\Enums\Status\ProjectStageStatus;
use App\Models\MsProjectWorkflow;
use App\Models\MsStatus;
use App\Models\TransProjectHd;
use Illuminate\Support\Collection;

class CreateProjectStagesService
{
    /** @param Collection<int, MsProjectWorkflow> $workflows */
    public function execute(TransProjectHd $project, Collection $workflows): void
    {
        $statusId = MsStatus::query()
            ->where('type', ProjectStageStatus::type()->value)
            ->where('code', ProjectStageStatus::Draft->value)
            ->where('active', true)
            ->firstOrFail()->id;

        foreach ($workflows as $workflow) {
            $project->stages()->create([
                'project_workflow_id' => $workflow->id,
                'sequence' => $workflow->sequence,
                'work_name' => $workflow->name,
                'progress_weight' => $workflow->progress_weight,
                'editable' => true,
                'status_id' => $statusId,
            ]);
        }
    }
}
