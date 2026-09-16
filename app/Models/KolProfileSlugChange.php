<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kol_profile_id',
    'old_slug',
    'new_slug',
    'changed_by',
    'reason',
])]
class KolProfileSlugChange extends Model
{
    public function kolProfile(): BelongsTo
    {
        return $this->belongsTo(KolProfile::class);
    }
}
