<?php

namespace App\Providers;

use App\Models\User;
use App\Services\KolSlugService;
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
        });
    }
}
