@extends('layouts.super')
@section('title', 'Tableau de bord')

@php
    $firstName   = \Illuminate\Support\Str::before(auth()->user()->full_name, ' ') ?: auth()->user()->full_name;
    $revenue12   = $monthly->sum('revenue');
    $thisMonth   = $monthly->last()['revenue'];
    $lastMonth   = $monthly->slice(-2, 1)->first()['revenue'];
    $delta       = $lastMonth > 0 ? (int) round(($thisMonth - $lastMonth) * 100 / $lastMonth) : null;
    $maxRevenue  = max(1, $monthly->max('revenue'));
    // Graduation « ronde » de l'axe : 1, 2 ou 5 × 10^n
    $pow  = 10 ** max(0, floor(log10($maxRevenue)));
    $step = collect([1, 2, 5, 10])->map(fn ($m) => $m * $pow)->first(fn ($s) => $maxRevenue / $s <= 4);
    $axisMax = $step * ceil($maxRevenue / $step);
    $orgs12  = $monthly->sum('orgs');
    $blocked = $stats['without_access'] + $stats['suspended'];
    $total   = max(1, array_sum($breakdown));
    $segments = [
        ['active', 'Actives', 'bg-emerald-500', 'text-emerald-600', 'fa-circle-check'],
        ['trial', 'En essai', 'bg-violet-500', 'text-violet-600', 'fa-flask'],
        ['none', 'Sans abonnement', 'bg-slate-300', 'text-slate-400', 'fa-hourglass-end'],
        ['suspended', 'Suspendues', 'bg-red-500', 'text-red-600', 'fa-ban'],
    ];
    $short = fn ($n) => $n >= 1_000_000 ? rtrim(rtrim(number_format($n / 1_000_000, 1, ',', ''), '0'), ',') . ' M'
                     : ($n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1, ',', ''), '0'), ',') . ' k' : (string) $n);
@endphp

@section('header')
<div class="flex flex-wrap items-end justify-between gap-6">
    <div>
        <p class="eyebrow"><i class="fa-regular fa-calendar"></i> {{ ucfirst(now()->translatedFormat('l j F Y')) }}</p>
        <h1 class="page-title mt-3">Bonjour {{ $firstName }}, <span class="accent">voici votre plateforme.</span></h1>
        <p class="page-subtitle">Revenus, entreprises clientes et échéances de {{ config('saas.platform_name') }} en un coup d'œil.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('super.subscriptions.index') }}" class="btn btn-light"><i class="fa-solid fa-receipt"></i> Abonnements</a>
        <a href="{{ route('super.organizations.create') }}" class="btn btn-dark"><i class="fa-solid fa-plus"></i> Entreprise</a>
    </div>
</div>
<div class="h-12"></div>
@endsection

@section('content')
{{-- ===== Indicateurs ===== --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 -mt-16 relative">
    {{-- Revenu mensuel : carte de contraste --}}
    <a href="{{ route('super.subscriptions.index', ['status' => 'current']) }}" class="card-ink p-6 group transition hover:-translate-y-0.5">
        <div class="absolute inset-0 dots-dark"></div>
        <div class="absolute -bottom-16 -right-10 w-48 h-48 rounded-full bg-orange-500/30 blur-3xl"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <p class="text-[13px] font-semibold text-slate-300">Revenu mensuel estimé</p>
                <span class="w-9 h-9 rounded-xl bg-orange-600 flex items-center justify-center shadow-cta"><i class="fa-solid fa-arrow-trend-up text-xs"></i></span>
            </div>
            <p class="mt-4 text-[30px] leading-none font-extrabold tracking-[-0.03em] tabular-nums">{{ number_format($stats['mrr'], 0, ',', ' ') }} <span class="text-sm font-semibold text-slate-400">XOF</span></p>
            <p class="mt-3 text-[13px] text-slate-400">{{ $stats['trials'] }} entreprise(s) en essai</p>
        </div>
    </a>

    @foreach([
        ['Encaissé ce mois', number_format($stats['revenue_month'], 0, ',', ' '), 'XOF', 'fa-wallet', route('super.subscriptions.index', ['year' => now()->year]), $delta],
        ['Entreprises actives', $stats['active'], '/ ' . $stats['organizations'], 'fa-building', route('super.organizations.index', ['status' => 'active']), null],
        ['Sans accès', $blocked, null, 'fa-lock', route('super.organizations.index', ['status' => 'expired']), null],
    ] as $i => [$label, $value, $unit, $icon, $href, $trend])
        <a href="{{ $href }}" class="card card-hover p-6">
            <div class="flex items-center justify-between">
                <p class="text-[13px] font-semibold text-slate-500">{{ $label }}</p>
                <span class="w-9 h-9 rounded-xl flex items-center justify-center {{ $i === 2 && $blocked > 0 ? 'bg-red-50 text-red-600' : 'bg-orange-50 text-orange-600' }}">
                    <i class="fa-solid {{ $icon }} text-xs"></i>
                </span>
            </div>
            <p class="mt-4 stat">{{ $value }} @if($unit)<span class="text-sm font-semibold text-slate-400">{{ $unit }}</span>@endif</p>
            <p class="mt-3 text-[13px] text-slate-500">
                @if($i === 0)
                    @if($trend !== null)
                        <span class="inline-flex items-center gap-1 font-bold {{ $trend >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            <i class="fa-solid {{ $trend >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} text-[10px]"></i>{{ abs($trend) }} %
                        </span> vs mois dernier
                    @else
                        {{ formatMoney($stats['revenue_year']) }} depuis janvier
                    @endif
                @elseif($i === 1)
                    <span class="font-bold text-slate-700">+{{ $orgs12 }}</span> sur 12 mois
                @else
                    {{ $stats['suspended'] }} suspendue(s) · {{ $stats['without_access'] }} expirée(s)
                @endif
            </p>
        </a>
    @endforeach
</div>

{{-- ===== Graphiques ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    {{-- Revenus encaissés sur 12 mois --}}
    <div class="card lg:col-span-2">
        <div class="card-header items-start">
            <div>
                <h2 class="card-title">Revenus encaissés</h2>
                <p class="card-subtitle">12 derniers mois, par date de paiement</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ formatMoney($revenue12) }}</p>
                <p class="text-xs text-slate-500">cumul sur la période</p>
            </div>
        </div>
        <div class="card-body pt-2" x-data="{ hover: null }">
            <div class="relative h-64 pl-12">
                {{-- Grille et axe --}}
                @foreach([1, 0.75, 0.5, 0.25, 0] as $f)
                    <div class="absolute left-12 right-0 border-t {{ $f === 0 ? 'border-slate-200' : 'border-dashed border-slate-100' }}" style="bottom: {{ $f * 100 }}%">
                        <span class="absolute -left-12 -translate-y-1/2 w-10 text-right text-[11px] font-medium text-slate-400 tabular-nums">{{ $short($axisMax * $f) }}</span>
                    </div>
                @endforeach
                {{-- Barres --}}
                <div class="absolute left-12 right-0 inset-y-0 flex items-end gap-2 sm:gap-3">
                    @foreach($monthly as $i => $m)
                        @php($h = $m['revenue'] > 0 ? max(2, $m['revenue'] * 100 / $axisMax) : 0)
                        <div class="relative flex-1 h-full flex items-end justify-center cursor-default" @mouseenter="hover = {{ $i }}" @mouseleave="hover = null">
                            <div class="absolute inset-x-0 inset-y-0 rounded-lg transition" :class="hover === {{ $i }} ? 'bg-orange-50/70' : ''"></div>
                            @if($h > 0)
                                <div class="relative w-full max-w-[28px] rounded-t-[4px] transition {{ $loop->last ? 'bg-orange-600' : 'bg-orange-300' }}"
                                     :class="hover === {{ $i }} ? '!bg-orange-600' : ''" style="height: {{ $h }}%"></div>
                            @else
                                <div class="relative w-full max-w-[28px] h-[2px] rounded-full bg-slate-200"></div>
                            @endif
                            {{-- Infobulle --}}
                            <div x-show="hover === {{ $i }}" x-cloak x-transition.opacity.duration.150ms
                                 class="absolute z-10 bottom-full mb-2 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-xl bg-ink px-3 py-2 text-white shadow-pop pointer-events-none"
                                 style="bottom: calc({{ $h }}% + 8px)">
                                <p class="text-[11px] text-slate-400">{{ $m['full'] }}</p>
                                <p class="text-sm font-bold tabular-nums">{{ formatMoney($m['revenue']) }}</p>
                                <p class="text-[11px] text-slate-400">{{ $m['orgs'] }} nouvelle(s) entreprise(s)</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- Mois --}}
            <div class="flex gap-2 sm:gap-3 pl-12 mt-3">
                @foreach($monthly as $m)
                    <span class="flex-1 text-center text-[11px] font-semibold {{ $loop->last ? 'text-orange-600' : 'text-slate-400' }}">{{ rtrim($m['label'], '.') }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Répartition des entreprises --}}
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Parc clients</h2>
                <p class="card-subtitle">Entreprises par état d'accès</p>
            </div>
        </div>
        <div class="card-body pt-2">
            <p class="stat">{{ $stats['organizations'] }} <span class="text-sm font-semibold text-slate-400">entreprises</span></p>
            <div class="mt-5 flex h-3 gap-[2px] rounded-full overflow-hidden bg-slate-100">
                @foreach($segments as [$key, $label, $bg])
                    @if($breakdown[$key] > 0)
                        <div class="{{ $bg }} h-full first:rounded-l-full last:rounded-r-full" style="width: {{ $breakdown[$key] * 100 / $total }}%" title="{{ $label }} : {{ $breakdown[$key] }}"></div>
                    @endif
                @endforeach
            </div>
            <ul class="mt-6 space-y-1">
                @foreach($segments as [$key, $label, $bg, $text, $icon])
                    <li class="flex items-center gap-3 py-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $bg }}"></span>
                        <span class="text-sm text-slate-600 flex-1">{{ $label }}</span>
                        <span class="text-sm font-bold text-slate-900 tabular-nums">{{ $breakdown[$key] }}</span>
                        <span class="w-12 text-right text-xs font-medium text-slate-400 tabular-nums">{{ round($breakdown[$key] * 100 / $total) }} %</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4">
                <div>
                    <p class="kicker">Utilisateurs</p>
                    <p class="mt-1 text-lg font-extrabold text-slate-900 tabular-nums">{{ number_format($stats['users'], 0, ',', ' ') }}</p>
                </div>
                <div>
                    <p class="kicker">Documents</p>
                    <p class="mt-1 text-lg font-extrabold text-slate-900 tabular-nums">{{ number_format($stats['documents'], 0, ',', ' ') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Actions & échéances ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="card lg:col-span-2 overflow-hidden">
        <div class="card-header">
            <div>
                <h2 class="card-title">À renouveler sous 30 jours</h2>
                <p class="card-subtitle">Périodes en cours sans renouvellement programmé</p>
            </div>
            <span class="badge {{ $expiringSoon->isEmpty() ? 'badge-green' : 'badge-amber' }}">{{ $expiringSoon->count() }} entreprise(s)</span>
        </div>
        @if($expiringSoon->isEmpty())
            <div class="empty border-t border-slate-100">
                <div class="empty-icon !text-emerald-500"><i class="fa-solid fa-check"></i></div>
                <p class="text-sm font-bold text-slate-900">Tout est à jour</p>
                <p class="text-sm text-slate-500 mt-1">Aucun abonnement ne se termine dans les 30 prochains jours.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data">
                    <thead><tr><th>Entreprise</th><th>Offre</th><th>Échéance</th><th></th></tr></thead>
                    <tbody>
                    @foreach($expiringSoon as $sub)
                        @php($days = $sub->daysRemaining())
                        <tr>
                            <td>
                                <a href="{{ route('super.organizations.show', $sub->organization) }}" class="flex items-center gap-3 group">
                                    <span class="avatar">{{ initials($sub->organization->name) }}</span>
                                    <span class="font-bold text-slate-900 group-hover:text-orange-600">{{ $sub->organization->name }}</span>
                                </a>
                            </td>
                            <td>
                                {{ $sub->plan->name }}
                                @if($sub->status === 'trial')<span class="badge badge-violet ml-1">Essai</span>@endif
                            </td>
                            <td class="whitespace-nowrap">
                                <p class="text-slate-800 font-semibold">{{ $sub->ends_at->format('d/m/Y') }}</p>
                                <p class="text-xs font-bold {{ $days <= 7 ? 'text-red-600' : 'text-amber-600' }}">J-{{ $days }}</p>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('super.subscriptions.create', $sub->organization) }}" class="btn btn-light btn-sm">Renouveler</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Centre d'actions --}}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">À traiter</h2>
        </div>
        <div class="px-3 pb-3 space-y-1">
            @foreach([
                [$newDemoRequests, 'Nouvelles demandes de démo', 'À contacter', 'fa-inbox', route('super.demo-requests.index', ['status' => 'new'])],
                [$expiringSoon->count(), 'Renouvellements proches', 'Sous 30 jours', 'fa-rotate', route('super.subscriptions.index', ['status' => 'current'])],
                [$stats['without_access'], 'Entreprises sans abonnement', 'Accès bloqué', 'fa-hourglass-end', route('super.organizations.index', ['status' => 'expired'])],
                [$stats['suspended'], 'Entreprises suspendues', 'À régulariser', 'fa-ban', route('super.organizations.index', ['status' => 'suspended'])],
            ] as [$count, $label, $hint, $icon, $href])
                <a href="{{ $href }}" class="flex items-center gap-4 p-3 rounded-2xl hover:bg-slate-50 transition group">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center {{ $count > 0 ? 'bg-orange-50 text-orange-600' : 'bg-slate-50 text-slate-400' }}">
                        <i class="fa-solid {{ $icon }} text-sm"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-900">{{ $label }}</p>
                        <p class="text-xs text-slate-500">{{ $hint }}</p>
                    </div>
                    <span class="text-lg font-extrabold tabular-nums {{ $count > 0 ? 'text-slate-900' : 'text-slate-300' }}">{{ $count }}</span>
                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-orange-500 transition"></i>
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- ===== Récents ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Nouvelles entreprises</h2>
            <a href="{{ route('super.organizations.index') }}" class="text-[13px] link">Tout voir</a>
        </div>
        <ul class="px-3 pb-3">
            @forelse($recentOrganizations as $org)
                <li>
                    <a href="{{ route('super.organizations.show', $org) }}" class="flex items-center gap-3 p-3 rounded-2xl hover:bg-slate-50 transition group">
                        <span class="avatar">{{ initials($org->name) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900 truncate group-hover:text-orange-600">{{ $org->name }}</p>
                            <p class="text-xs text-slate-500">{{ $org->created_at->diffForHumans() }}</p>
                        </div>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-orange-500"></i>
                    </a>
                </li>
            @empty
                <li class="empty"><p class="text-sm text-slate-500">Aucune entreprise.</p></li>
            @endforelse
        </ul>
    </div>

    <div class="card lg:col-span-2 overflow-hidden">
        <div class="card-header">
            <h2 class="card-title">Activité récente</h2>
            <a href="{{ route('super.activity.index') }}" class="text-[13px] link">Voir le journal</a>
        </div>
        @include('super.activity._list', ['logs' => $recentActivity])
    </div>
</div>
@endsection
