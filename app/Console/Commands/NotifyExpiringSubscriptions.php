<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Tenant;
use Illuminate\Console\Command;

class NotifyExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:notify-expiring';

    protected $description = 'Prévenir les entreprises et le super admin des abonnements qui se terminent bientôt';

    public function handle(NotificationService $service): int
    {
        $count = 0;

        foreach ([7, 3, 1] as $days) {
            $subscriptions = Subscription::with('organization', 'plan')
                ->where('status', '!=', 'cancelled')
                ->whereDate('ends_at', today()->addDays($days))
                ->get();

            foreach ($subscriptions as $subscription) {
                $organization = $subscription->organization;

                // Déjà renouvelé : une période suivante est prévue
                $renewed = Subscription::where('organization_id', $organization->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('starts_at', '>', $subscription->ends_at)
                    ->exists();
                if ($renewed || $organization->isSuspended()) {
                    continue;
                }

                $label   = $days === 1 ? 'demain' : "dans {$days} jours";
                $message = "Votre abonnement {$subscription->plan->name} se termine {$label} ({$subscription->ends_at->format('d/m/Y')}). "
                    . 'Contactez ' . config('saas.vendor_name') . ' (' . config('saas.support_email') . ', ' . config('saas.support_phone') . ') pour le renouveler.';

                $admins = User::with('roles')->where('organization_id', $organization->id)->where('is_active', true)->get()
                    ->filter(fn (User $u) => $u->hasRole('admin'));

                Tenant::run($organization, function () use ($admins, $service, $message) {
                    foreach ($admins as $admin) {
                        $service->notify($admin, 'subscription_expiring', 'Abonnement bientôt terminé', $message, url('/dashboard'));
                    }
                });

                // Copie au super admin (SMTP de la plateforme)
                foreach (User::where('is_super_admin', true)->get() as $superAdmin) {
                    $service->notify(
                        $superAdmin,
                        'subscription_expiring',
                        "Fin d'abonnement : {$organization->name}",
                        "L'abonnement de « {$organization->name} » se termine {$label} ({$subscription->ends_at->format('d/m/Y')}).",
                        route('super.organizations.show', $organization)
                    );
                }

                $count++;
            }
        }

        $this->info("{$count} rappel(s) de fin d'abonnement envoyé(s).");

        return self::SUCCESS;
    }
}
