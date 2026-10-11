<?php

namespace App\Models;

use Database\Factories\TransProjectMonitoringFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class TransProjectMonitoring extends Model
{
    /** @use HasFactory<TransProjectMonitoringFactory> */
    use HasFactory;

    protected $table = 'trans_project_monitoring';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Project monitoring is append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Project monitoring is append-only.');
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(TransProjectHd::class, 'project_hd_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(TransProjectDt::class, 'project_dt_id');
    }

    public function statusBefore(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_before_id');
    }

    public function statusAfter(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_after_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
