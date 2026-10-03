<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsItemUnit extends Model
{
    use AuditedBySoftDelete, SoftDeletes;

    protected $table = 'ms_item_unit';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_base' => 'boolean',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MsItem::class, 'item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MsUnit::class, 'unit_id');
    }
}
