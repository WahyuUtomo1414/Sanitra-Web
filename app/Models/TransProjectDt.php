<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransProjectDtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransProjectDt extends Model
{
    /** @use HasFactory<TransProjectDtFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_project_dt';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'editable' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(TransProjectHd::class, 'project_hd_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(MsProjectWorkflow::class, 'project_workflow_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_id');
    }

    public function monitoring(): HasMany
    {
        return $this->hasMany(TransProjectMonitoring::class, 'project_dt_id');
    }
}
