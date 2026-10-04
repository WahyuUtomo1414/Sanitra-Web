<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use App\Traits\LogsMasterActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsProjectWorkflow extends Model
{
    use AuditedBySoftDelete, LogsMasterActivity, SoftDeletes;

    protected $table = 'ms_project_workflow';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(MsProjectType::class, 'type_id');
    }
}
