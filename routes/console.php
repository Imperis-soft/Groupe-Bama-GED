<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Les tâches documentaires (expiration, rappels, purge) sont planifiées dans bootstrap/app.php

// Signal de vie du planificateur (le journal système signale un cron arrêté)
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever(\App\Services\SystemHealth::HEARTBEAT_KEY, time()))
    ->everyMinute()->name('system:heartbeat');

// Contrôles de santé de la plateforme → journal système
Schedule::command('system:health')->everyTenMinutes();

// Journal système : problèmes résolus depuis plus de 90 jours supprimés
Schedule::call(fn () => \App\Models\SystemEvent::whereNotNull('resolved_at')->where('resolved_at', '<', now()->subDays(90))->delete())
    ->dailyAt('03:45')->name('system:prune');

// Exports complets expirés : fichier supprimé du stockage — chaque nuit
Schedule::call(function () {
    \App\Models\OrganizationExport::withoutGlobalScopes()->where('status', 'done')->where('expires_at', '<', now())->get()
        ->each(function ($export) {
            \App\Models\Organization::find($export->organization_id)?->disk()->delete($export->path);
            $export->update(['status' => 'expired']);
        });
})->dailyAt('03:30')->name('exports:prune');

// Libérer les verrous expirés — toutes les heures
Schedule::call(function () {
    \App\Models\DocumentLock::where('expires_at', '<', now())->delete();
})->hourly();
