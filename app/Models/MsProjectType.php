<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsProjectType extends Model
{
    use AuditedBySoftDelete, SoftDeletes;

    protected $table = 'ms_project_type';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(MsCompany::class, 'company_id');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(MsProjectWorkflow::class, 'type_id');
    }
}
