<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeSequence extends Model
{
    protected $table = 'sys_code_sequence';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
