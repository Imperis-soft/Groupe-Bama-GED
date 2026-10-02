<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Document;
use App\Models\Category;
use App\Models\User;
use App\Models\ApprovalStep;

class DashboardController extends Controller
{

    // Afficher le tableau de bord avec les métriques clés
    public function index(Request $request)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        // Basic counts — admin voit tout, les autres voient leurs docs + partagés
        $baseQuery = fn() => $isAdmin ? Document::query() : Document::visibleTo();

        $documentsCount    = $baseQuery()->count();
        $categoriesCount   = Category::count();
        $usersCount        = $isAdmin ? User::count() : null;

        // Archival metrics
        $archivedCount     = $baseQuery()->where('status', 'archived')->count();
        $expiredCount      = $baseQuery()->expired()->count();
        $confidentialCount = $isAdmin ? Document::confidential()->count() : null;
        $draftCount        = $baseQuery()->where('status', 'draft')->count();
        $reviewCount       = $baseQuery()->where('status', 'review')->count();

        // Recent documents (visibles uniquement)
        $recentDocuments = $baseQuery()->latest()->limit(6)->get();

        // Recent audit activities (admin voit tout, autres voient leurs docs)
        $recentActivities = \App\Models\DocumentAuditLog::with(['document', 'user'])
            ->when(!$isAdmin, function ($q) use ($user) {
                $q->whereHas('document', function ($d) use ($user) {
                    $d->visibleTo($user->id);
                });
            })
            ->latest()
            ->limit(10)
            ->get();

        // Abonnement de l'entreprise : offre, quotas et échéance (admin uniquement)
        $subscriptionInfo = $isAdmin ? $this->subscriptionInfo() : null;

        // Graphiques : documents ajoutés et actions journalisées par jour sur 12 mois
        $chart = $this->activityChart($baseQuery, $isAdmin, $user);

        // Répartition des documents par statut
        $statusBreakdown = $baseQuery()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($total, $status) => [statusLabel($status) => (int) $total])
            ->sortDesc();

        // Métriques spécifiques aux non-admins
        $sharedWithMeCount   = 0;
        $pendingApprovalsCount = 0;
        if (!$isAdmin) {
            $sharedWithMeCount = \App\Models\DocumentShare::where('shared_with', $user->id)
                ->where('is_active', true)
                ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
                ->count();

            // Uniquement les étapes dont c'est le tour de l'utilisateur
            $pendingApprovalsCount = app(\App\Services\InboxService::class)->approvalsQuery($user)->count();
        }

        $inboxCount = app(\App\Services\InboxService::class)->count($user);

        return view('dashboard', compact(
            'inboxCount',
            'documentsCount', 'categoriesCount', 'usersCount', 'recentDocuments',
            'archivedCount', 'expiredCount', 'confidentialCount', 'draftCount', 'reviewCount', 'recentActivities',
            'subscriptionInfo', 'chart', 'statusBreakdown',
            'isAdmin', 'sharedWithMeCount', 'pendingApprovalsCount'
        ));
    }

    // Offre en cours, consommation des quotas et échéance de l'abonnement
    private function subscriptionInfo(): ?array
    {
        $organization = \App\Support\Tenant::organization();
        if (!$organization) {
            return null;
        }

        $subscription = $organization->activeSubscription() ?? $organization->latestSubscription();
        $plan         = $subscription?->plan;

        $storageUsed = $organization->storageUsedBytes();
        $storageMax  = $plan?->max_storage_mb ? $plan->max_storage_mb * 1024 * 1024 : null;
        $usersUsed   = $organization->usersCount();
        $usersMax    = $plan?->max_users;

        $periodDays  = $subscription ? max(1, $subscription->starts_at->diffInDays($subscription->ends_at)) : null;
        $daysLeft    = $subscription?->daysRemaining();

        return [
            'organization'  => $organization,
            'subscription'  => $subscription,
            'plan'          => $plan,
            'storageUsed'   => $storageUsed,
            'storageMax'    => $storageMax,
            'storagePct'    => $storageMax ? min(100, round($storageUsed / $storageMax * 100, 1)) : null,
            'usersUsed'     => $usersUsed,
            'usersMax'      => $usersMax,
            'usersPct'      => $usersMax ? min(100, round($usersUsed / $usersMax * 100, 1)) : null,
            'daysLeft'      => $daysLeft,
            'periodPct'     => $periodDays ? max(0, min(100, round(($periodDays - max(0, $daysLeft)) / $periodDays * 100))) : null,
        ];
    }

    // Séries quotidiennes (365 jours) : documents ajoutés et actions du journal d'audit
    private function activityChart(\Closure $baseQuery, bool $isAdmin, User $user): array
    {
        $from = today()->subDays(364);

        $documents = $baseQuery()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $actions = \App\Models\DocumentAuditLog::query()
            ->when(!$isAdmin, fn ($q) => $q->whereHas('document', fn ($d) => $d->visibleTo($user->id)))
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = [];
        for ($date = $from->copy(); $date->lte(today()); $date->addDay()) {
            $key    = $date->toDateString();
            $days[] = [
                'date'      => $key,
                'documents' => (int) ($documents[$key] ?? 0),
                'actions'   => (int) ($actions[$key] ?? 0),
            ];
        }

        return $days;
    }
}
