<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'confirmation_code',
    'source',
    'identifier_hash',
    'status',
    'completed_at',
])]
class DataDeletionRequest extends Model
{
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
