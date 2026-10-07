<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use App\Traits\LogsMasterActivity;
use Database\Factories\MsClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsClient extends Model
{
    /** @use HasFactory<MsClientFactory> */
    use AuditedBySoftDelete, HasFactory, LogsMasterActivity, SoftDeletes;

    protected $table = 'ms_client';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function picEmployee(): BelongsTo
    {
        return $this->belongsTo(MsEmployee::class, 'pic_employee_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(MsDocument::class, 'reference', 'ref_type', 'ref_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(MsClientContact::class, 'client_id');
    }
}
