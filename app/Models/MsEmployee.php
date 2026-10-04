<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use App\Traits\LogsMasterActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsEmployee extends Model
{
    use AuditedBySoftDelete, LogsMasterActivity, SoftDeletes;

    protected $table = 'ms_employee';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(MsCompany::class, 'company_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id');
    }
}
