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
        // Force HTTPS en production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Register alias middleware for role checks
        if (class_exists(Route::class)) {
            Route::aliasMiddleware('role', RoleMiddleware::class);
        }
    }
}
