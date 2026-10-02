<?php

namespace App\Console\Commands;

use App\Services\SystemHealth;
use Illuminate\Console\Command;

// Contrôles de santé de la plateforme (toutes les 10 minutes) → journal système de la console super admin
class SystemHealthCheck extends Command
{
    protected $signature = 'system:health';

    protected $description = 'Contrôle la santé de la plateforme et met à jour le journal système';

    public function handle(SystemHealth $health): int
    {
        foreach ($health->run() as $check) {
            $icon = ['ok' => '✔', 'warning' => '!', 'error' => '✘'][$check['status']];
            $this->line("{$icon} {$check['label']} : {$check['detail']}");
        }

        return self::SUCCESS;
    }
}
