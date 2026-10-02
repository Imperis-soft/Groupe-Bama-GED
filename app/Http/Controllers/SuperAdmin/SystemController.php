<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SystemEvent;
use App\Services\SystemHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Journal système (console super admin) : état de santé de la plateforme et problèmes détectés.
 */
class SystemController extends Controller
{
    public function index(Request $request, SystemHealth $health)
    {
        // Contrôles de santé : relancés au plus une fois par minute (ou à la demande)
        if ($request->boolean('refresh')) {
            Cache::forget('system:health-checks');
        }
        $checks = Cache::remember('system:health-checks', 60, fn () => $health->run());

        $filters = $request->validate([
            'status'       => 'nullable|in:open,resolved,all',
            'level'        => ['nullable', 'in:' . implode(',', array_keys(SystemEvent::LEVELS))],
            'category'     => ['nullable', 'in:' . implode(',', array_keys(SystemEvent::CATEGORIES))],
            'organization' => 'nullable|integer',
        ]);
        $status = $filters['status'] ?? 'open';

        $events = SystemEvent::with('organization', 'resolver')
            ->when($status === 'open', fn ($q) => $q->whereNull('resolved_at'))
            ->when($status === 'resolved', fn ($q) => $q->whereNotNull('resolved_at'))
            ->when($filters['level'] ?? null, fn ($q, $level) => $q->where('level', $level))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['organization'] ?? null, fn ($q, $id) => $q->where('organization_id', $id))
            ->orderByRaw("case level when 'critical' then 0 when 'error' then 1 when 'warning' then 2 else 3 end")
            ->orderByDesc('last_seen_at')
            ->paginate(30)->withQueryString();

        $counts = SystemEvent::open()->selectRaw('level, count(*) as total')->groupBy('level')->pluck('total', 'level');
        $organizations = Organization::orderBy('name')->get(['id', 'name']);
        $highlight = (int) $request->query('event');

        return view('super.system.index', compact('checks', 'events', 'counts', 'organizations', 'filters', 'status', 'highlight'));
    }

    public function resolve(SystemEvent $event)
    {
        $event->update(['resolved_at' => now(), 'resolved_by' => auth()->id()]);

        return back()->with('success', 'Problème marqué comme résolu. S\'il se reproduit, il réapparaîtra.');
    }

    public function resolveAll(Request $request)
    {
        $count = SystemEvent::open()
            ->when($request->input('level'), fn ($q, $level) => $q->where('level', $level))
            ->when($request->input('category'), fn ($q, $category) => $q->where('category', $category))
            ->update(['resolved_at' => now(), 'resolved_by' => auth()->id()]);

        return back()->with('success', "{$count} problème(s) marqué(s) comme résolu(s).");
    }
}
