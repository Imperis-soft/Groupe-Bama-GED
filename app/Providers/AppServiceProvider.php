<?php

namespace App\Providers;

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
