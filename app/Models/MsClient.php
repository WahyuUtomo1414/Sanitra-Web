<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsClient extends Model
{
    use AuditedBySoftDelete, SoftDeletes;

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
}
