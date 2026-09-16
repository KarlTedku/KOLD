<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'display_name',
    'slug',
    'slug_locked_at',
    'bio',
    'card_headline',
    'external_contact_url',
    'card_theme',
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
            'slug_locked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cardLinks(): HasMany
    {
        return $this->hasMany(KolCardLink::class)->orderBy('sort_order')->orderBy('id');
    }

    public function aiTags(): HasMany
    {
        return $this->hasMany(KolAiTag::class);
    }

    public function approvedAiTags(): HasMany
    {
        return $this->aiTags()->where('status', 'approved');
    }

    public function slugAliases(): HasMany
    {
        return $this->hasMany(KolProfileSlugAlias::class);
    }

    public function slugChanges(): HasMany
    {
        return $this->hasMany(KolProfileSlugChange::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isSlugLocked(): bool
    {
        return $this->slug_locked_at !== null;
    }

    public function totalFollowers(): int
    {
        return (int) $this->user?->socialAccounts?->sum('follower_count');
    }

    /**
     * @return array<int, string>
     */
    public function cardPublicationIssues(): array
    {
        $issues = [];

        if (! filled($this->display_name)) {
            $issues[] = '請先填寫顯示名稱。';
        }

        if (! filled($this->slug)) {
            $issues[] = '請先設定公開短網址。';
        }

        if (! $this->cardLinks()->where('is_active', true)->exists()) {
            $issues[] = '請最少啟用一個卡片連結。';
        }

        return $issues;
    }

    public function canPublishCard(): bool
    {
        return $this->cardPublicationIssues() === [];
    }
}
