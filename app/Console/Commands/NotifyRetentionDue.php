<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Organization;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Tenant;
use Illuminate\Console\Command;

/**
 * Rappel hebdomadaire aux administrateurs : documents arrivés en fin de conservation, en attente de décision.
 * Ne décide et ne supprime rien.
 */
class NotifyRetentionDue extends Command
{
    protected $signature = 'documents:retention-review';

    protected $description = 'Prévient les administrateurs des documents arrivés en fin de conservation';

    public function handle(NotificationService $service): int
    {
        $notified = 0;

        $counts = Document::withoutGlobalScope('organization')->retentionDue()
            ->selectRaw('organization_id, count(*) as total')->groupBy('organization_id')->pluck('total', 'organization_id');

        foreach ($counts as $organizationId => $total) {
            $organization = Organization::find($organizationId);
            if (!$organization || $organization->isSuspended()) {
                continue;
            }

            $admins = User::withoutGlobalScopes()->with('roles')->where('organization_id', $organizationId)->where('is_active', true)->get()
                ->filter(fn (User $user) => $user->hasRole('admin'));

            Tenant::run($organization, function () use ($admins, $service, $total, &$notified) {
                foreach ($admins as $admin) {
                    $service->notify($admin, 'retention_due', 'Fin de conservation',
                        "{$total} document(s) sont arrivés au terme de leur durée de conservation et attendent votre décision (éliminer, prolonger ou conserver).",
                        route('retention.index'));
                    $notified++;
                }
            });
        }

        $this->info("{$notified} administrateur(s) prévenu(s).");

        return self::SUCCESS;
    }
}
