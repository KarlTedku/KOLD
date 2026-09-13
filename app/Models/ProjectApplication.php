<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['brand_project_id', 'kol_user_id', 'pitch', 'proposed_rate', 'status', 'responded_at', 'contact_request_id'])]
class ProjectApplication extends Model
{
    protected function casts(): array
    {
        return ['proposed_rate' => 'integer', 'responded_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(BrandProject::class, 'brand_project_id');
    }

    public function kol(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kol_user_id');
    }

    public function contactRequest(): BelongsTo
    {
        return $this->belongsTo(ContactRequest::class);
    }

    public function collaboration(): HasOne
    {
        return $this->hasOne(Collaboration::class);
    }
}
