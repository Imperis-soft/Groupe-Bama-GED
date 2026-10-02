<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use App\Http\Middleware\RoleMiddleware;

class AppServiceProvider extends ServiceProvider
{
    // Register any application services.
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates en français quelle que soit APP_LOCALE : l'interface est entièrement en français
        Carbon::setLocale('fr');

        // Tâche en arrière-plan en échec définitif (export, OCR, copie PDF/A…) : journal système
        \Illuminate\Support\Facades\Queue::failing(function (\Illuminate\Queue\Events\JobFailed $event) {
            \App\Models\SystemEvent::record('error', 'queue',
                'Tâche en échec : ' . class_basename($event->job->resolveName()) . ' — ' . $event->exception->getMessage(),
                ['job' => $event->job->resolveName(), 'exception' => get_class($event->exception)],
                null, hash('sha256', 'queue|' . $event->job->resolveName() . '|' . get_class($event->exception)));
        });

        // Forcer l'URL racine depuis APP_URL (proxy Caddy HTTPS)
        $appUrl = config('app.url');
        if ($appUrl) {
            URL::forceRootUrl($appUrl);
        }

        // Force HTTPS si APP_URL commence par https
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        // Register alias middleware for role checks
        if (class_exists(Route::class)) {
            Route::aliasMiddleware('role', RoleMiddleware::class);
        }
    }
}
