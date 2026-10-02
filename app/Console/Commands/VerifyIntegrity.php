<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use App\Services\IntegrityService;
use App\Services\NotificationService;
use App\Support\Tenant;
use Illuminate\Console\Command;

/**
 * Contrôle d'intégrité hebdomadaire : fichiers, procès-verbaux et chaîne du journal d'audit de chaque entreprise.
 * En cas d'anomalie, les administrateurs de l'entreprise et le super admin sont prévenus.
 */
class VerifyIntegrity extends Command
{
    protected $signature = 'documents:verify-integrity {--organization= : Identifiant d\'une seule entreprise}';

    protected $description = 'Vérifie l\'intégrité des fichiers et du journal d\'audit de chaque entreprise';

    public function handle(IntegrityService $integrity, NotificationService $notifications): int
    {
        $organizations = Organization::query()
            ->when($this->option('organization'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        $failures = 0;
        foreach ($organizations as $organization) {
            $check = $integrity->check($organization);
            $this->line(sprintf('%s : %d fichier(s) contrôlé(s), %d anomalie(s), journal %s.',
                $organization->name, $check->files_checked, $check->files_failed, $check->audit_chain_ok ? 'intact' : 'ROMPU'));

            if ($check->passed()) {
                continue;
            }
            $failures++;
            $message = "Contrôle d'intégrité du " . now()->format('d/m/Y') . ' : '
                . ($check->files_failed ? "{$check->files_failed} fichier(s) introuvable(s) ou modifié(s) hors de la plateforme" : '')
                . ($check->files_failed && !$check->audit_chain_ok ? ' ; ' : '')
                . ($check->audit_chain_ok ? '' : 'le journal d\'audit a été altéré')
                . '. ' . implode(' ', array_slice($check->problems ?? [], 0, 3));

            $admins = User::withoutGlobalScopes()->with('roles')->where('organization_id', $organization->id)->where('is_active', true)->get()
                ->filter(fn (User $user) => $user->hasRole('admin'));
            Tenant::run($organization, function () use ($admins, $notifications, $message) {
                foreach ($admins as $admin) {
                    $notifications->notify($admin, 'integrity_alert', 'Alerte d\'intégrité', $message, route('retention.index', ['tab' => 'integrity']));
                }
            });
            foreach (User::where('is_super_admin', true)->get() as $superAdmin) {
                $notifications->notify($superAdmin, 'integrity_alert', "Alerte d'intégrité : {$organization->name}", $message, route('super.organizations.show', $organization));
            }
        }

        $failures ? $this->error("{$failures} entreprise(s) avec des anomalies.") : $this->info('Aucune anomalie.');

        return self::SUCCESS;
    }
}
