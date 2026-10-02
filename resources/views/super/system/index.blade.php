@extends('layouts.super')
@section('title', 'Système')

@php
    $statusStyle = [
        'ok'      => ['fa-circle-check', 'text-emerald-600', 'bg-emerald-50'],
        'warning' => ['fa-triangle-exclamation', 'text-amber-600', 'bg-amber-50'],
        'error'   => ['fa-circle-xmark', 'text-red-600', 'bg-red-50'],
    ];
    $levelBadge = ['critical' => 'badge-red', 'error' => 'badge-red', 'warning' => 'badge-amber', 'info' => 'badge-blue'];
    $failing = collect($checks)->where('status', '!=', 'ok')->count();
@endphp

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-heart-pulse"></i> Administration</p>
        <h1 class="page-title">Système</h1>
        <p class="page-subtitle">Santé de la plateforme et problèmes détectés automatiquement (erreurs, tâches en échec, emails, stockage, intégrité).</p>
    </div>
    <a href="{{ route('super.system.index', ['refresh' => 1] + request()->except('refresh')) }}" class="btn btn-light"><i class="fa-solid fa-rotate"></i> Relancer les contrôles</a>
</div>
@endsection

@section('content')

{{-- ================= SANTÉ ================= --}}
<div class="card overflow-hidden mb-6">
    <div class="card-header">
        <div>
            <h2 class="card-title">{{ $failing ? "{$failing} point(s) à surveiller" : 'Tout fonctionne' }}</h2>
            <p class="card-subtitle">Contrôles relancés toutes les 10 minutes par le planificateur, et à chaque visite de cette page (au plus une fois par minute).</p>
        </div>
        <span class="w-10 h-10 rounded-xl flex items-center justify-center {{ $failing ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
            <i class="fa-solid {{ $failing ? 'fa-stethoscope' : 'fa-shield-heart' }}"></i>
        </span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-px bg-slate-100 border-t border-slate-100">
        @foreach($checks as $check)
        @php [$icon, $color, $bg] = $statusStyle[$check['status']]; @endphp
        <div class="flex items-start gap-3 px-6 py-4 bg-white">
            <span class="w-8 h-8 rounded-lg {{ $bg }} {{ $color }} flex items-center justify-center shrink-0"><i class="fa-solid {{ $icon }} text-sm"></i></span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900">{{ $check['label'] }}</p>
                <p class="text-xs mt-0.5 {{ $check['status'] === 'ok' ? 'text-slate-500' : $color }}">{{ $check['detail'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ================= PROBLÈMES ================= --}}
<div class="card overflow-hidden">
    <div class="card-header flex-wrap gap-3">
        <div>
            <h2 class="card-title">Problèmes</h2>
            <p class="card-subtitle">
                @forelse(['critical', 'error', 'warning'] as $level)
                    @if($counts[$level] ?? 0)<span class="badge {{ $levelBadge[$level] }} mr-1">{{ $counts[$level] }} {{ strtolower(\App\Models\SystemEvent::LEVELS[$level]) }}{{ $counts[$level] > 1 ? 's' : '' }}</span>@endif
                @empty
                @endforelse
                @if($counts->sum() === 0) Aucun problème ouvert. @endif
            </p>
        </div>
        @if($status === 'open' && $events->total())
        <form method="POST" action="{{ route('super.system.resolve-all') }}" onsubmit="return confirm('Marquer tous les problèmes affichés comme résolus ?')">
            @csrf
            <input type="hidden" name="level" value="{{ $filters['level'] ?? '' }}">
            <input type="hidden" name="category" value="{{ $filters['category'] ?? '' }}">
            <button class="btn btn-light btn-sm"><i class="fa-solid fa-check-double"></i> Tout marquer résolu</button>
        </form>
        @endif
    </div>

    {{-- Filtres --}}
    <form method="GET" class="px-6 py-4 bg-slate-50/70 border-b border-slate-100 grid grid-cols-2 lg:grid-cols-4 gap-3">
        <select name="status" class="field field-sm" onchange="this.form.submit()">
            @foreach(['open' => 'Ouverts', 'resolved' => 'Résolus', 'all' => 'Tous'] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="level" class="field field-sm" onchange="this.form.submit()">
            <option value="">Toutes gravités</option>
            @foreach(\App\Models\SystemEvent::LEVELS as $value => $label)
                <option value="{{ $value }}" @selected(($filters['level'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category" class="field field-sm" onchange="this.form.submit()">
            <option value="">Toutes catégories</option>
            @foreach(\App\Models\SystemEvent::CATEGORIES as $value => $label)
                <option value="{{ $value }}" @selected(($filters['category'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="organization" class="field field-sm" onchange="this.form.submit()">
            <option value="">Toutes entreprises</option>
            @foreach($organizations as $organization)
                <option value="{{ $organization->id }}" @selected((int) ($filters['organization'] ?? 0) === $organization->id)>{{ $organization->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="divide-y divide-slate-100">
        @forelse($events as $event)
        <div x-data="{ open: {{ $highlight === $event->id ? 'true' : 'false' }} }" class="px-6 py-4 {{ $highlight === $event->id ? 'bg-orange-50/40' : '' }}">
            <div class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5 mb-1">
                        <span class="badge {{ $levelBadge[$event->level] ?? 'badge-slate' }}">{{ \App\Models\SystemEvent::LEVELS[$event->level] ?? $event->level }}</span>
                        <span class="badge badge-slate">{{ \App\Models\SystemEvent::CATEGORIES[$event->category] ?? $event->category }}</span>
                        @if($event->organization)<span class="badge badge-blue">{{ $event->organization->name }}</span>@endif
                        @if($event->occurrences > 1)<span class="text-xs font-bold text-slate-500">× {{ number_format($event->occurrences, 0, ',', ' ') }}</span>@endif
                        @if($event->resolved_at)<span class="badge badge-green">Résolu{{ $event->resolver ? ' par ' . $event->resolver->full_name : ' automatiquement' }}</span>@endif
                    </div>
                    <p class="text-sm font-semibold text-slate-900 break-words">{{ $event->message }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        Dernière fois {{ $event->last_seen_at->diffForHumans() }} ({{ $event->last_seen_at->format('d/m/Y H:i') }})
                        @if($event->occurrences > 1) · première le {{ $event->first_seen_at->format('d/m/Y H:i') }} @endif
                        @if($event->context) · <button type="button" @click="open = !open" class="font-semibold text-orange-600 hover:underline" x-text="open ? 'Masquer le détail' : 'Voir le détail'"></button>@endif
                    </p>
                </div>
                @unless($event->resolved_at)
                <form method="POST" action="{{ route('super.system.resolve', $event) }}">
                    @csrf
                    <button class="btn btn-light btn-sm" title="Le problème est corrigé"><i class="fa-solid fa-check"></i> Résolu</button>
                </form>
                @endunless
            </div>
            @if($event->context)
            <div x-show="open" x-cloak class="mt-3 rounded-xl bg-slate-900 text-slate-200 text-xs font-mono p-4 overflow-x-auto">
                @foreach($event->context as $key => $value)
                    <div class="flex gap-3 py-0.5">
                        <span class="text-orange-300 shrink-0 w-24">{{ $key }}</span>
                        <span class="whitespace-pre-wrap break-all">{{ is_array($value) ? implode("\n", array_map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v, $value)) : $value }}</span>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="empty">
            <div class="empty-icon"><i class="fa-solid fa-shield-heart"></i></div>
            <p class="font-bold text-slate-900">{{ $status === 'open' ? 'Aucun problème ouvert' : 'Aucun problème' }}</p>
            <p class="text-sm text-slate-500 mt-1">Les erreurs, tâches en échec, envois d'email ratés et alertes d'intégrité apparaîtront ici automatiquement.</p>
        </div>
        @endforelse
    </div>
</div>
<div class="mt-4">{{ $events->links() }}</div>
@endsection
