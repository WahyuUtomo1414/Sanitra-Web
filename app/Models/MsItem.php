<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsItem extends Model
{
    use AuditedBySoftDelete, SoftDeletes;

    protected $table = 'ms_item';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MsItemCategory::class, 'item_category_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(MsItemUnit::class, 'item_id');
    }
}
