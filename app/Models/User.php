<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'avatar',
    'role',
    'provider',
    'provider_id',
    'email_verified_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isKol(): bool
    {
        return $this->role === 'kol';
    }

    public function isBrand(): bool
    {
        return $this->role === 'brand';
    }

    public function hasRole(): bool
    {
        return in_array($this->role, ['kol', 'brand'], true);
    }

    public function kolProfile(): HasOne
    {
        return $this->hasOne(KolProfile::class);
    }

    public function brandProfile(): HasOne
    {
        return $this->hasOne(BrandProfile::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function sentContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'from_user_id');
    }

    public function receivedContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'to_user_id');
    }

    public function profileDisplayName(): string
    {
        if ($this->isKol()) {
            return $this->kolProfile?->display_name ?? $this->name;
        }

        if ($this->isBrand()) {
            return $this->brandProfile?->company_name ?? $this->name;
        }

        return $this->name;
    }

    public function isPublished(): bool
    {
        if ($this->isKol()) {
            return $this->kolProfile?->status === 'published';
        }

        if ($this->isBrand()) {
            return $this->brandProfile?->status === 'published';
        }

        return false;
    }
}
