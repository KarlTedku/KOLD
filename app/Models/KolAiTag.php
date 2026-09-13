<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kol_profile_id',
    'category',
    'label',
    'status',
    'confidence',
    'rationale',
    'source',
])]
class KolAiTag extends Model
{
    protected function casts(): array
    {
        return [
            'confidence' => 'integer',
        ];
    }

    public function kolProfile(): BelongsTo
    {
        return $this->belongsTo(KolProfile::class);
    }
}
