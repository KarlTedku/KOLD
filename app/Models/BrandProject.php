<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'brand_user_id', 'title', 'slug', 'brief', 'niches', 'regions', 'platforms',
    'campaign_objective', 'target_audience', 'collaboration_formats',
    'compensation_type', 'usage_rights',
    'budget_min', 'budget_max', 'deliverables', 'application_deadline',
    'campaign_start_date', 'campaign_end_date', 'status', 'is_sample',
])]
class BrandProject extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'niches' => 'array', 'regions' => 'array', 'platforms' => 'array',
            'collaboration_formats' => 'array',
            'budget_min' => 'integer', 'budget_max' => 'integer',
            'application_deadline' => 'date', 'campaign_start_date' => 'date',
            'campaign_end_date' => 'date',
            'is_sample' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(User::class, 'brand_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProjectApplication::class);
    }

    public function collaborations(): HasMany
    {
        return $this->hasMany(Collaboration::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'published'
            && (! $this->application_deadline || $this->application_deadline->copy()->endOfDay()->isFuture());
    }
}
