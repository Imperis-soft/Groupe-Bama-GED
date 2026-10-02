<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount([
            'subscriptions as current_count' => fn ($q) => $q->where('status', '!=', 'cancelled')
                ->whereDate('starts_at', '<=', today())->whereDate('ends_at', '>=', today()),
        ])->orderBy('sort_order')->get();

        return view('super.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('super.plans.form', ['plan' => new Plan(['currency' => 'XOF', 'billing_months' => 1, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $plan = Plan::create($this->validated($request));
        PlatformActivityLog::record('plan_created', "Offre « {$plan->name} » créée");

        return redirect()->route('super.plans.index')->with('success', "Offre « {$plan->name} » créée.");
    }

    public function edit(Plan $plan)
    {
        return view('super.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($this->validated($request, $plan));
        PlatformActivityLog::record('plan_updated', "Offre « {$plan->name} » modifiée");

        return redirect()->route('super.plans.index')->with('success', "Offre « {$plan->name} » mise à jour.");
    }

    // Une offre déjà utilisée est désactivée plutôt que supprimée (historique des abonnements)
    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            $plan->update(['is_active' => false]);
            PlatformActivityLog::record('plan_deactivated', "Offre « {$plan->name} » désactivée");
            return back()->with('success', "L'offre « {$plan->name} » a des abonnements : elle est désactivée (plus proposée) au lieu d'être supprimée.");
        }

        $plan->delete();
        PlatformActivityLog::record('plan_deleted', "Offre « {$plan->name} » supprimée");

        return back()->with('success', "Offre « {$plan->name} » supprimée.");
    }

    private function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'slug'           => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'description'    => 'nullable|string|max:500',
            'price'          => 'required|integer|min:0',
            'currency'       => 'required|string|size:3',
            'billing_months' => 'required|integer|in:1,3,6,12',
            'max_users'      => 'nullable|integer|min:1',
            'max_storage_mb' => 'nullable|integer|min:1',
            'features'       => 'nullable|string|max:2000',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $data['slug']       = $data['slug'] ?: Str::slug($data['name']);
        $data['currency']   = strtoupper($data['currency']);
        $data['is_active']  = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        // Une ligne = un argument commercial
        $data['features']   = array_values(array_filter(array_map('trim', explode("\n", $data['features'] ?? ''))));

        return $data;
    }
}
