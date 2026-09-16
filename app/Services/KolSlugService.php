<?php

namespace App\Services;

use App\Models\KolProfile;
use App\Models\KolProfileSlugAlias;
use App\Models\KolProfileSlugChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KolSlugService
{
    public function normalize(string $slug): string
    {
        return mb_strtolower(trim($slug));
    }

    /** @return array<int, mixed> */
    public function validationRules(KolProfile $profile, bool $allowOwnedAlias = false): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:50',
            'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
            function (string $attribute, mixed $value, \Closure $fail) use ($profile, $allowOwnedAlias): void {
                $slug = $this->normalize((string) $value);

                if ($slug === $profile->slug) {
                    return;
                }

                if (in_array($slug, config('kold.reserved_slugs', []), true)) {
                    $fail('呢個公開網址屬於系統保留名稱，請選擇其他名稱。');

                    return;
                }

                if (KolProfile::query()->where('slug', $slug)->where('id', '!=', $profile->id)->exists()) {
                    $fail('呢個公開網址已被使用，請選擇其他名稱。');

                    return;
                }

                $alias = KolProfileSlugAlias::query()->where('slug', $slug)->first();
                if ($alias && (! $allowOwnedAlias || $alias->kol_profile_id !== $profile->id)) {
                    $fail('呢個公開網址已被永久保留，請選擇其他名稱。');
                }
            },
        ];
    }

    public function renameByOperator(
        string $currentSlug,
        string $newSlug,
        string $actor,
        string $reason
    ): KolProfile {
        $currentSlug = $this->normalize($currentSlug);
        $newSlug = $this->normalize($newSlug);
        $actor = trim($actor);
        $reason = trim($reason);
        $profile = KolProfile::query()->where('slug', $currentSlug)->firstOrFail();

        $validator = Validator::make([
            'new_slug' => $newSlug,
            'actor' => $actor,
            'reason' => $reason,
        ], [
            'new_slug' => $this->validationRules($profile, allowOwnedAlias: true),
            'actor' => ['required', 'string', 'max:120'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($currentSlug === $newSlug) {
            $validator->after(fn ($validator) => $validator->errors()->add('new_slug', '新舊公開網址不可相同。'));
        }

        $validator->validate();

        return DB::transaction(function () use ($profile, $currentSlug, $newSlug, $actor, $reason): KolProfile {
            $profile = KolProfile::query()->lockForUpdate()->findOrFail($profile->id);

            KolProfileSlugAlias::query()->firstOrCreate([
                'slug' => $currentSlug,
            ], [
                'kol_profile_id' => $profile->id,
            ]);

            $profile->update(['slug' => $newSlug]);

            KolProfileSlugChange::query()->create([
                'kol_profile_id' => $profile->id,
                'old_slug' => $currentSlug,
                'new_slug' => $newSlug,
                'changed_by' => $actor,
                'reason' => $reason,
            ]);

            return $profile->refresh();
        });
    }

    public function reserveBeforeAccountDeletion(User $user): void
    {
        $profile = $user->kolProfile()->first();

        if (! $profile || blank($profile->slug) || (! $profile->isSlugLocked() && ! $profile->isPublished())) {
            return;
        }

        KolProfileSlugAlias::query()->firstOrCreate([
            'slug' => $profile->slug,
        ], [
            'kol_profile_id' => $profile->id,
        ]);
    }
}
