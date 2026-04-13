<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nettoyage des documents expirés — tous les jours à minuit
Schedule::command('documents:cleanup-expired')->dailyAt('00:00');

// Libérer les verrous expirés — toutes les heures
Schedule::call(function () {
    \App\Models\DocumentLock::where('expires_at', '<', now())->delete();
})->hourly();
