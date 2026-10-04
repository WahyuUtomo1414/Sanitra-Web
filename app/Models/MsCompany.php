<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use App\Traits\LogsMasterActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsCompany extends Model
{
    use AuditedBySoftDelete, LogsMasterActivity, SoftDeletes;

    protected $table = 'ms_company';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function picEmployee(): BelongsTo
    {
        return $this->belongsTo(MsEmployee::class, 'pic_employee_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(MsEmployee::class, 'company_id');
    }

    public function projectTypes(): HasMany
    {
        return $this->hasMany(MsProjectType::class, 'company_id');
    }
}
