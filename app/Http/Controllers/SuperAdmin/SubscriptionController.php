<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformActivityLog;
use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with('organization', 'plan', 'creator')->latest('starts_at')->latest('id');

        if ($orgId = $request->input('organization')) {
            $query->where('organization_id', $orgId);
        }

        match ($request->input('status')) {
            'current'   => $query->where('status', '!=', 'cancelled')->whereDate('starts_at', '<=', today())->whereDate('ends_at', '>=', today()),
            'expired'   => $query->where('status', '!=', 'cancelled')->whereDate('ends_at', '<', today()),
            'trial'     => $query->where('status', 'trial'),
            'cancelled' => $query->where('status', 'cancelled'),
            default     => null,
        };

        if ($year = $request->input('year')) {
            $query->whereYear('paid_at', $year);
        }

        $totalAmount   = (clone $query)->where('status', '!=', 'cancelled')->sum('amount');
        $subscriptions = $query->paginate(25)->withQueryString();
        $organizations = Organization::orderBy('name')->get(['id', 'name']);

        return view('super.subscriptions.index', compact('subscriptions', 'organizations', 'totalAmount'));
    }

    // Formulaire de nouvel abonnement / renouvellement
    public function create(Organization $organization)
    {
        $plans  = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $latest = $organization->latestSubscription();

        // Le renouvellement démarre au lendemain de la période en cours, ou aujourd'hui si elle est terminée
        $defaultStart = $latest && $latest->ends_at->gte(today())
            ? $latest->ends_at->copy()->addDay()
            : today();

        return view('super.subscriptions.create', compact('organization', 'plans', 'latest', 'defaultStart'));
    }

    public function store(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'plan_id'           => ['required', Rule::exists('plans', 'id')],
            'status'            => 'required|in:active,trial',
            'starts_at'         => 'required|date',
            'months'            => 'required|integer|min:1|max:60',
            'amount'            => 'required|integer|min:0',
            'payment_method'    => ['nullable', Rule::in(array_keys(Subscription::PAYMENT_METHODS))],
            'payment_reference' => 'nullable|string|max:255',
            'paid_at'           => 'nullable|date',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $start = Carbon::parse($data['starts_at'])->startOfDay();
        $plan  = Plan::findOrFail($data['plan_id']);

        $subscription = Subscription::create([
            'organization_id'   => $organization->id,
            'plan_id'           => $plan->id,
            'status'            => $data['status'],
            'starts_at'         => $start,
            'ends_at'           => $start->copy()->addMonths((int) $data['months'])->subDay(),
            'amount'            => $data['amount'],
            'currency'          => $plan->currency,
            'payment_method'    => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'paid_at'           => $data['paid_at'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'created_by'        => auth()->id(),
        ]);

        PlatformActivityLog::record(
            'subscription_created',
            "Abonnement {$plan->name} du {$subscription->starts_at->format('d/m/Y')} au {$subscription->ends_at->format('d/m/Y')} ({$subscription->formattedAmount()})",
            $organization
        );

        $this->notifyOrganizationAdmins(
            $organization,
            'subscription_renewed',
            'Abonnement mis à jour',
            "Votre abonnement {$plan->name} est valable du {$subscription->starts_at->format('d/m/Y')} au {$subscription->ends_at->format('d/m/Y')}."
        );

        return redirect()->route('super.organizations.show', $organization)->with('success', 'Abonnement enregistré.');
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'notes'        => trim(($subscription->notes ? $subscription->notes . "\n" : '') . 'Annulé : ' . ($request->input('reason') ?: 'sans motif')),
        ]);

        PlatformActivityLog::record(
            'subscription_cancelled',
            "Abonnement {$subscription->plan->name} ({$subscription->starts_at->format('d/m/Y')} → {$subscription->ends_at->format('d/m/Y')}) annulé",
            $subscription->organization
        );

        return back()->with('success', 'Abonnement annulé.');
    }

    // Notification in-app (et email si configuré) aux administrateurs de l'entreprise
    private function notifyOrganizationAdmins(Organization $organization, string $type, string $title, string $message): void
    {
        $admins = User::with('roles')
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $u) => $u->hasRole('admin'));

        Tenant::run($organization, function () use ($admins, $type, $title, $message) {
            $service = app(NotificationService::class);
            foreach ($admins as $admin) {
                $service->notify($admin, $type, $title, $message, url('/dashboard'));
            }
        });
    }
}
