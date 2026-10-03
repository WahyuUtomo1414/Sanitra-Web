<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsItemCategory extends Model
{
    use AuditedBySoftDelete, SoftDeletes;

    protected $table = 'ms_item_category';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MsItem::class, 'item_category_id');
    }
}
