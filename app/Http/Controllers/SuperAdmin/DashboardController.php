<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use App\Models\Document;
use App\Models\Organization;
use App\Models\PlatformActivityLog;
use App\Models\Subscription;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $current = Subscription::with('plan', 'organization')
            ->where('status', '!=', 'cancelled')
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->get();

        $activeOrgIds = $current->pluck('organization_id')->unique();
        $suspendedCount = Organization::where('status', 'suspended')->count();

        // Revenu mensuel récurrent estimé : montant de chaque période en cours ramené au mois
        $mrr = $current->where('status', 'active')->sum(function (Subscription $s) {
            $months = max(1, $s->starts_at->diffInMonths($s->ends_at->copy()->addDay()));
            return $s->amount / $months;
        });

        $stats = [
            'organizations'   => Organization::count(),
            'active'          => Organization::whereIn('id', $activeOrgIds)->where('status', 'active')->count(),
            'suspended'       => $suspendedCount,
            'without_access'  => Organization::whereNotIn('id', $activeOrgIds)->count(),
            'trials'          => $current->where('status', 'trial')->count(),
            'users'           => User::where('is_super_admin', false)->count(),
            'documents'       => Document::count(),
            'mrr'             => (int) round($mrr),
            'revenue_month'   => (int) Subscription::where('status', '!=', 'cancelled')
                                    ->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('amount'),
            'revenue_year'    => (int) Subscription::where('status', '!=', 'cancelled')
                                    ->whereYear('paid_at', now()->year)->sum('amount'),
        ];

        // Abonnements qui se terminent dans les 30 jours (sans renouvellement déjà prévu)
        $expiringSoon = $current
            ->filter(fn (Subscription $s) => $s->ends_at->lte(today()->addDays(30)))
            ->reject(fn (Subscription $s) => Subscription::where('organization_id', $s->organization_id)
                ->where('status', '!=', 'cancelled')
                ->whereDate('starts_at', '>', $s->ends_at)
                ->exists())
            ->sortBy('ends_at')
            ->values();

        // Séries des 12 derniers mois (mois courant inclus) pour les graphiques
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $paid = Subscription::where('status', '!=', 'cancelled')
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $months->first())
            ->get(['amount', 'paid_at']);
        $created = Organization::whereDate('created_at', '>=', $months->first())->get(['created_at']);

        $monthly = $months->map(fn ($m) => [
            'label'   => ucfirst($m->translatedFormat('M')),
            'full'    => ucfirst($m->translatedFormat('F Y')),
            'revenue' => (int) $paid->filter(fn ($s) => $s->paid_at->isSameMonth($m))->sum('amount'),
            'orgs'    => $created->filter(fn ($o) => $o->created_at->isSameMonth($m))->count(),
        ]);

        // Répartition des entreprises par état d'accès
        $trialOrgIds = $current->where('status', 'trial')->pluck('organization_id')->unique();
        $breakdown = [
            'active'    => Organization::where('status', 'active')->whereIn('id', $activeOrgIds)->whereNotIn('id', $trialOrgIds)->count(),
            'trial'     => Organization::where('status', 'active')->whereIn('id', $trialOrgIds)->count(),
            'none'      => Organization::where('status', '!=', 'suspended')->whereNotIn('id', $activeOrgIds)->count(),
            'suspended' => $suspendedCount,
        ];

        $newDemoRequests     = DemoRequest::where('status', 'new')->count();
        $recentOrganizations = Organization::latest()->take(5)->get();
        $recentActivity      = PlatformActivityLog::with('user', 'organization')->latest()->take(6)->get();

        return view('super.dashboard', compact(
            'stats', 'expiringSoon', 'recentOrganizations', 'recentActivity', 'monthly', 'breakdown', 'newDemoRequests'
        ));
    }
}
