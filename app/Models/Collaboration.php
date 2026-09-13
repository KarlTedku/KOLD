<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'brand_user_id', 'kol_user_id', 'brand_project_id', 'project_application_id',
    'contact_request_id', 'conversation_id', 'updated_by_user_id', 'title', 'status',
])]
class Collaboration extends Model
{
    public const STATUSES = ['negotiating', 'confirmed', 'in_progress', 'completed'];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(User::class, 'brand_user_id');
    }

    public function kol(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kol_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(BrandProject::class, 'brand_project_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ProjectApplication::class, 'project_application_id');
    }

    public function contactRequest(): BelongsTo
    {
        return $this->belongsTo(ContactRequest::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'confirmed' => '已確認', 'in_progress' => '進行中', 'completed' => '已完成',
            default => '洽談中',
        };
    }
}
