<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Faire confiance au proxy Caddy (HTTPS → HTTP)
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            '/webdav/*',
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Nettoyage des documents expirés chaque nuit à 02h00
        $schedule->command('documents:cleanup-expired')->dailyAt('02:00');
        // Rappels d'expiration : chaque matin à 08h00
        $schedule->command('documents:notify-expiring')->dailyAt('08:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
