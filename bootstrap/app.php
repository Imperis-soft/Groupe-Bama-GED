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
        // Faire confiance au reverse proxy (TLS sur l'hôte → nginx du conteneur en HTTP)
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            '/webdav/*',
        ]);

        // Alias pour les middlewares personnalisés
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'tenant' => \App\Http\Middleware\SetTenant::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // L'entreprise courante doit être connue AVANT la liaison des modèles de route ({document}…),
        // sinon un identifiant d'une autre entreprise serait résolu sans filtre.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\SetTenant::class,
        );
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Aucune suppression automatique à échéance : la date d'échéance ne déclenche que des rappels.
        // (La destruction en fin de conservation passera par un circuit d'élimination validé.)
        // Rappels d'échéance : chaque matin à 08h00
        $schedule->command('documents:notify-expiring')->dailyAt('08:00');
        // Relance des validations en retard (approbateur, et auteur au premier retard)
        $schedule->command('approvals:remind')->weekdays()->at('09:00');
        // Fin de conservation : rappel aux administrateurs chaque lundi (aucune décision automatique)
        $schedule->command('documents:retention-review')->weeklyOn(1, '08:15');
        // Contrôle d'intégrité (fichiers, procès-verbaux, journal d'audit) : chaque dimanche à 04h00
        $schedule->command('documents:verify-integrity')->weeklyOn(0, '04:00');
        // Purge automatique de la corbeille (documents supprimés depuis 30+ jours)
        $schedule->command('documents:purge-trash --days=30')->dailyAt('03:00');
        // Rappels de fin d'abonnement (entreprises + super admin) : chaque matin à 08h30
        $schedule->command('subscriptions:notify-expiring')->dailyAt('08:30');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Toute erreur signalée est aussi inscrite au journal système (console super admin), regroupée par type et emplacement
        $exceptions->reportable(function (\Throwable $e) {
            \App\Models\SystemEvent::fromException($e);
        });
    })->create();
