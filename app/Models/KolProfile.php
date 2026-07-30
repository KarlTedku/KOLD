<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'display_name',
    'bio',
    'niches',
    'regions',
    'languages',
    'age_range',
    'photos',
    'rate_min',
    'rate_max',
    'status',
])]
class KolProfile extends Model
{
    protected function casts(): array
    {
        return [
            'niches' => 'array',
            'regions' => 'array',
            'languages' => 'array',
            'photos' => 'array',
            'rate_min' => 'integer',
            'rate_max' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function totalFollowers(): int
    {
        return (int) $this->user?->socialAccounts?->sum('follower_count');
    }
}
