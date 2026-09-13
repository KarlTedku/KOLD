<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kol_profile_id',
    'title',
    'url',
    'type',
    'sort_order',
    'is_active',
])]
class KolCardLink extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function kolProfile(): BelongsTo
    {
        return $this->belongsTo(KolProfile::class);
    }
}
