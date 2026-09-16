<?php

use App\Services\KolSlugService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command(
    'kold:rename-kol-slug {current} {new} {--actor=} {--reason=}',
    function (KolSlugService $slugs): int {
        try {
            $profile = $slugs->renameByOperator(
                (string) $this->argument('current'),
                (string) $this->argument('new'),
                (string) $this->option('actor'),
                (string) $this->option('reason'),
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return 1;
        }

        $this->info("KOL public URL changed to /k/{$profile->slug}. The previous URL remains reserved and redirects permanently.");

        return 0;
    }
)->purpose('Rename a locked KOL public URL with an audit reason and permanent redirect');
