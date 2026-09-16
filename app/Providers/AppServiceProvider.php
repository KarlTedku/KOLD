<?php

namespace App\Providers;

use App\Models\User;
use App\Services\KolSlugService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(KolSlugService $slugs): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        User::deleting(function (User $user) use ($slugs): void {
            $slugs->reserveBeforeAccountDeletion($user);

            $profile = $user->kolProfile()->first(['id', 'card_background_path']);
            $path = $profile?->card_background_path;

            if (! $path || ! str_starts_with($path, "card-backgrounds/{$profile->id}/")) {
                return;
            }

            $removeBackground = static fn () => Storage::disk('public')->delete($path);

            if (DB::transactionLevel() > 0) {
                DB::afterCommit($removeBackground);
            } else {
                $removeBackground();
            }
        });
    }
}
