<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'platform',
    'account_type',
    'external_id',
    'page_id',
    'page_name',
    'handle',
    'follower_count',
    'metrics_json',
    'is_primary',
    'access_token',
    'refresh_token',
    'synced_at',
])]
#[Hidden(['access_token', 'refresh_token'])]
class SocialAccount extends Model
{
    protected function casts(): array
    {
        return [
            'metrics_json' => 'array',
            'follower_count' => 'integer',
            'is_primary' => 'boolean',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
