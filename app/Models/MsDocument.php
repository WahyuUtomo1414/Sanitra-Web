<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use App\Traits\LogsMasterActivity;
use Database\Factories\MsDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsDocument extends Model
{
    /** @use HasFactory<MsDocumentFactory> */
    use AuditedBySoftDelete, HasFactory, LogsMasterActivity, SoftDeletes;

    protected $table = 'ms_document';

    protected $guarded = ['id', 'ref_type', 'ref_id'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'ref_id' => 'integer',
        ];
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'ref_type', 'ref_id');
    }
}
