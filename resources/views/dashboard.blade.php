@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">
                Bonjour, {{ explode(' ', auth()->user()->full_name)[0] }} 👋
            </h1>
            <p class="text-xs text-slate-400 font-medium mt-1">
                {{ now()->translatedFormat('l d F Y') }} — Tableau de bord GED
            </p>
        </div>
        <a href="{{ route('documents.index') }}#import"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-3 rounded-xl shadow-lg shadow-orange-200 transition-all self-start sm:self-auto">
            <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i> Ajouter des documents
        </a>
    </div>

    {{-- ===== À TRAITER ===== --}}
    @if($inboxCount > 0)
    <a href="{{ route('inbox.index') }}"
       class="flex items-center gap-4 bg-orange-50 hover:bg-orange-100/70 border border-orange-100 rounded-2xl px-5 py-4 transition-colors group">
        <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center shrink-0 shadow-md shadow-orange-200">
            <i class="fa-solid fa-inbox text-white text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-black text-orange-900">{{ $inboxCount }} élément(s) attendent votre action</p>
            <p class="text-[11px] text-orange-700/70">Approbations, signatures, documents à corriger et nouveaux partages</p>
        </div>
        <i class="fa-solid fa-arrow-right text-orange-500 group-hover:translate-x-1 transition-transform"></i>
    </a>
    @endif

    {{-- ===== KPI CARDS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">

        {{-- Documents --}}
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                        {{ $isAdmin ? 'Documents' : 'Mes documents' }}
                    </p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($documentsCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-lines text-blue-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-[9px] font-bold text-green-600 uppercase tracking-wider">Stockage actif</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-blue-50/60 group-hover:bg-blue-100/60 transition-colors"></div>
        </div>

        {{-- Catégories (admin) / Partagés avec moi (non-admin) --}}
        @if($isAdmin)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Catégories</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($categoriesCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-folder-tree text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Structure indexée</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-purple-50/60 group-hover:bg-purple-100/60 transition-colors"></div>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Partagés</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($sharedWithMeCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-share-nodes text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Avec moi</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-purple-50/60 group-hover:bg-purple-100/60 transition-colors"></div>
        </div>
        @endif

        {{-- Utilisateurs (admin) / Approbations en attente (non-admin) --}}
        @if($isAdmin)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Utilisateurs</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($usersCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-users text-orange-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5">
                <i class="fa-solid fa-shield-check text-orange-500 text-[9px]"></i>
                <span class="text-[9px] font-bold text-orange-600 uppercase tracking-wider">Accès sécurisés</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-orange-50/60 group-hover:bg-orange-100/60 transition-colors"></div>
        </div>
        @else
        <a href="{{ route('inbox.index') }}" class="block bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">À approuver</p>
                    <p class="text-3xl font-black {{ $pendingApprovalsCount > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-1 leading-none">{{ number_format($pendingApprovalsCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl {{ $pendingApprovalsCount > 0 ? 'bg-amber-50' : 'bg-slate-50' }} flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-list-check {{ $pendingApprovalsCount > 0 ? 'text-amber-500' : 'text-slate-400' }} text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold {{ $pendingApprovalsCount > 0 ? 'text-amber-500' : 'text-slate-400' }} uppercase tracking-wider">
                    {{ $pendingApprovalsCount > 0 ? 'Action requise' : 'Aucune en attente' }}
                </span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full {{ $pendingApprovalsCount > 0 ? 'bg-amber-50/60' : 'bg-slate-50/60' }} group-hover:opacity-80 transition-colors"></div>
        </a>
        @endif

        {{-- Expirés --}}
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Échéance dépassée</p>
                    <p class="text-3xl font-black {{ $expiredCount > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1 leading-none">{{ number_format($expiredCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl {{ $expiredCount > 0 ? 'bg-red-50' : 'bg-slate-50' }} flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation {{ $expiredCount > 0 ? 'text-red-500' : 'text-slate-400' }} text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold {{ $expiredCount > 0 ? 'text-red-500' : 'text-slate-400' }} uppercase tracking-wider">
                    {{ $expiredCount > 0 ? 'À revoir' : 'Aucune échéance dépassée' }}
                </span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full {{ $expiredCount > 0 ? 'bg-red-50/60' : 'bg-slate-50/60' }} group-hover:opacity-80 transition-colors"></div>
        </div>

    </div>

    {{-- ===== MON ABONNEMENT (admin) ===== --}}
    @if($subscriptionInfo)
    @php
        $si = $subscriptionInfo;
        $fmtBytes = function ($bytes) {
            if ($bytes >= 1024 ** 3) return number_format($bytes / 1024 ** 3, 1, ',', ' ') . ' Go';
            if ($bytes >= 1024 ** 2) return number_format($bytes / 1024 ** 2, 1, ',', ' ') . ' Mo';
            return number_format($bytes / 1024, 0, ',', ' ') . ' Ko';
        };
        $gaugeTone = fn ($pct) => $pct === null ? 'emerald' : ($pct >= 90 ? 'red' : ($pct >= 75 ? 'amber' : 'emerald'));
        $daysLeft = $si['daysLeft'];
        $expiryTone = $daysLeft === null || $daysLeft < 0 ? 'red' : ($daysLeft <= 15 ? 'red' : ($daysLeft <= 30 ? 'amber' : 'emerald'));
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 md:gap-4">

        {{-- Offre & échéance --}}
        <div class="relative overflow-hidden rounded-2xl p-5 text-white shadow-xl shadow-slate-300/40 bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800">
            <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full bg-orange-500/20 blur-2xl"></div>
            <div class="absolute -bottom-20 -left-10 w-40 h-40 rounded-full bg-orange-600/10 blur-2xl"></div>
            <div class="relative flex flex-col h-full">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400">Mon abonnement</p>
                        <h2 class="text-2xl font-black mt-1 leading-none">{{ $si['plan']?->name ?? 'Aucune offre' }}</h2>
                        <p class="text-[11px] text-slate-400 mt-1">{{ $si['organization']->name }}</p>
                    </div>
                    @if($si['subscription'])
                    @php $st = $si['subscription']->displayStatus(); @endphp
                    <span class="shrink-0 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider
                        {{ in_array($st, ['active', 'trial']) ? 'bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/30' : 'bg-red-400/15 text-red-300 ring-1 ring-red-400/30' }}">
                        {{ $si['subscription']->displayStatusLabel() }}
                    </span>
                    @endif
                </div>

                @if($si['subscription'])
                <div class="mt-6">
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Expire le</p>
                            <p class="text-sm font-black mt-0.5">{{ $si['subscription']->ends_at->translatedFormat('d F Y') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-black leading-none
                                {{ $expiryTone === 'red' ? 'text-red-400' : ($expiryTone === 'amber' ? 'text-amber-300' : 'text-white') }}">
                                {{ max(0, $daysLeft) }}
                            </p>
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mt-1">jour(s) restant(s)</p>
                        </div>
                    </div>
                    <div class="mt-3 h-1.5 rounded-full bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700
                            {{ $expiryTone === 'red' ? 'bg-red-400' : ($expiryTone === 'amber' ? 'bg-amber-300' : 'bg-gradient-to-r from-orange-500 to-amber-300') }}"
                             style="width: {{ $si['periodPct'] }}%"></div>
                    </div>
                    <div class="flex justify-between mt-1.5 text-[9px] text-slate-500 font-mono">
                        <span>{{ $si['subscription']->starts_at->format('d/m/Y') }}</span>
                        <span>{{ $si['subscription']->ends_at->format('d/m/Y') }}</span>
                    </div>
                </div>
                @endif

                @if($si['plan'])
                <p class="mt-auto pt-4 text-[10px] text-slate-400">
                    {{ $si['plan']->formattedPrice() }} / {{ $si['plan']->periodLabel() }}
                    @if($daysLeft !== null && $daysLeft <= 30)
                        <span class="text-amber-300 font-bold">— pensez à renouveler</span>
                    @endif
                </p>
                @endif
            </div>
        </div>

        {{-- Jauges stockage / utilisateurs --}}
        @foreach([
            ['key' => 'storage', 'title' => 'Espace de stockage', 'icon' => 'fa-hard-drive',
             'pct' => $si['storagePct'],
             'used' => $fmtBytes($si['storageUsed']),
             'max' => $si['storageMax'] ? $fmtBytes($si['storageMax']) : null,
             'free' => $si['storageMax'] ? $fmtBytes(max(0, $si['storageMax'] - $si['storageUsed'])) . ' disponibles' : 'Stockage illimité'],
            ['key' => 'users', 'title' => 'Utilisateurs', 'icon' => 'fa-users',
             'pct' => $si['usersPct'],
             'used' => number_format($si['usersUsed']),
             'max' => $si['usersMax'] !== null ? number_format($si['usersMax']) : null,
             'free' => $si['usersMax'] !== null ? max(0, $si['usersMax'] - $si['usersUsed']) . ' place(s) disponible(s)' : 'Utilisateurs illimités'],
        ] as $g)
        @php $tone = $gaugeTone($g['pct']); @endphp
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center gap-4">
            <div class="relative w-32 h-32 shrink-0">
                <div id="gauge-{{ $g['key'] }}" class="absolute inset-0"
                     data-pct="{{ $g['pct'] ?? 100 }}" data-tone="{{ $tone }}" data-unlimited="{{ $g['pct'] === null ? 1 : 0 }}"></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    @if($g['pct'] !== null)
                        <span class="text-xl font-black text-slate-900 leading-none">{{ rtrim(rtrim(number_format($g['pct'], 1, ',', ''), '0'), ',') }}%</span>
                        <span class="text-[8px] font-black uppercase tracking-widest text-slate-400 mt-1">utilisé</span>
                    @else
                        <i class="fa-solid fa-infinity text-emerald-500 text-xl"></i>
                    @endif
                </div>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <i class="fa-solid {{ $g['icon'] }} text-slate-400 text-xs"></i>
                    <p class="text-[10px] font-black text-slate-900 uppercase tracking-widest">{{ $g['title'] }}</p>
                </div>
                <p class="mt-2 leading-none">
                    <span class="text-2xl font-black text-slate-900">{{ $g['used'] }}</span>
                    @if($g['max'])<span class="text-xs font-bold text-slate-400"> / {{ $g['max'] }}</span>@endif
                </p>
                <p class="mt-2 text-[10px] font-bold
                    {{ $tone === 'red' ? 'text-red-500' : ($tone === 'amber' ? 'text-amber-600' : 'text-emerald-600') }}">
                    @if($tone === 'red') <i class="fa-solid fa-triangle-exclamation mr-0.5"></i> Quota presque atteint
                    @else {{ $g['free'] }} @endif
                </p>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== GRAPHIQUES ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-6">

        {{-- Activité dans le temps --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-5" x-data="{ period: 30 }">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Activité documentaire</h2>
                    </div>
                    <div class="flex items-center gap-5 mt-3">
                        <div>
                            <p class="text-2xl font-black text-slate-900 leading-none" id="chart-total-docs">—</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-sm bg-orange-500"></span> Documents ajoutés
                            </p>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div>
                            <p class="text-2xl font-black text-slate-900 leading-none" id="chart-total-actions">—</p>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-sm bg-slate-700"></span> Actions
                            </p>
                        </div>
                        <span id="chart-trend" class="hidden sm:inline-flex self-start px-2 py-1 rounded-lg text-[10px] font-black"></span>
                    </div>
                </div>
                <div class="inline-flex bg-slate-100 rounded-xl p-1 self-start">
                    @foreach([7 => '7 j', 30 => '30 j', 90 => '90 j', 365 => '12 mois'] as $days => $label)
                    <button type="button"
                            @click="period = {{ $days }}; window.gedDashboard.setPeriod({{ $days }})"
                            :class="period === {{ $days }} ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                            class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>
            <div id="activity-chart" class="mt-2 -mx-2" style="min-height: 300px"></div>
        </div>

        {{-- Répartition par statut --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Documents par statut</h2>
            </div>
            @if($statusBreakdown->sum() > 0)
                <div id="status-chart" class="flex-1 flex items-center justify-center mt-2" style="min-height: 300px"></div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center py-12 text-center">
                    <i class="fa-solid fa-chart-pie text-slate-200 text-3xl mb-3"></i>
                    <p class="text-xs font-bold text-slate-400">Aucun document pour l'instant</p>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== MAIN GRID ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-6">

        {{-- Documents récents (2/3) --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Activité récente</h2>
                </div>
                <a href="{{ route('documents.index') }}"
                   class="text-[10px] font-bold text-slate-400 hover:text-orange-600 transition-colors flex items-center gap-1">
                    Tout voir <i class="fa-solid fa-arrow-right text-[8px]"></i>
                </a>
            </div>

            <div class="divide-y divide-slate-50">
                @forelse($recentDocuments as $doc)
                <a href="{{ route('documents.show', $doc) }}"
                   class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/70 transition-colors group">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 group-hover:bg-orange-600 group-hover:text-white transition-all">
                        <x-file-icon :document="$doc" class="text-sm group-hover:text-white transition-colors" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-800 truncate leading-tight">{{ $doc->title }}</p>
                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                            {{ $doc->reference }}
                            <span class="mx-1 text-slate-200">•</span>
                            {{ $doc->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="hidden sm:inline-block px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase
                            {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                               ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' :
                                                              'bg-slate-100 text-slate-500') }}">
                            {{ statusLabel($doc->status) }}
                        </span>
                        <span class="hidden md:inline-block px-2 py-0.5 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold">
                            {{ $doc->category?->name ?? 'Général' }}
                        </span>
                    </div>
                </a>
                @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-inbox text-slate-300 text-xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400">Aucun document pour l'instant</p>
                    <a href="{{ route('documents.index') }}" class="mt-3 text-[10px] font-black text-orange-600 hover:underline uppercase tracking-wider">
                        Créer le premier
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Colonne droite (1/3) --}}
        <div class="flex flex-col gap-4">

            {{-- Conformité --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-900 uppercase tracking-widest mb-4">Conformité</h2>
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-slate-900">{{ number_format($archivedCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Archivés</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black {{ $expiredCount > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ number_format($expiredCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Échus</p>
                    </div>
                    @if($isAdmin)
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-purple-600">{{ number_format($confidentialCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Confidentiels</p>
                    </div>
                    @else
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-amber-600">{{ number_format($draftCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Brouillons</p>
                    </div>
                    @endif
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-blue-600">{{ number_format($reviewCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">En révision</p>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="bg-slate-900 rounded-2xl p-5 text-white shadow-xl shadow-slate-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-orange-600/20 rounded-full -translate-y-8 translate-x-8"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-orange-600/10 rounded-full translate-y-6 -translate-x-4"></div>
                <div class="relative">
                    <p class="text-[8px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Action rapide</p>
                    <h3 class="text-sm font-black mb-1 leading-snug">Archiver un nouveau document</h3>
                    <p class="text-[10px] text-slate-400 mb-4">Déposez vos fichiers, quel que soit leur format.</p>
                    <a href="{{ route('documents.index') }}#import"
                       class="flex items-center justify-center gap-2 w-full bg-orange-600 hover:bg-orange-500 text-white py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95">
                        <i class="fa-solid fa-cloud-arrow-up text-[9px]"></i> Ajouter des documents
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- ===== FAVORIS ===== --}}
    @php $favs = auth()->user()->favorites()->with('category')->latest('document_favorites.created_at')->limit(5)->get(); @endphp
    @if($favs->count())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-star text-amber-400 text-sm"></i>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Mes favoris</h2>
            </div>
            <a href="{{ route('documents.favorites') }}" class="text-[10px] font-bold text-slate-400 hover:text-orange-600 transition-colors">
                Voir tous <i class="fa-solid fa-arrow-right text-[8px]"></i>
            </a>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($favs as $doc)
            <a href="{{ route('documents.show', $doc) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/60 transition-colors group">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                    <x-file-icon :document="$doc" class="text-xs group-hover:text-white transition-colors" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ $doc->title }}</p>
                    <p class="text-[9px] text-slate-400 font-mono">{{ $doc->reference }}</p>
                </div>
                <span class="text-[9px] font-bold text-slate-400 shrink-0">{{ $doc->category?->name ?? 'Général' }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ===== AUDIT LOG ===== --}}
    @if($recentActivities->count())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Journal d'audit</h2>
            </div>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($recentActivities->take(5) as $activity)
            <div class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50/50 transition-colors">
                <div class="w-7 h-7 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-bolt text-orange-500 text-[9px]"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-800 truncate">
                        <span class="text-orange-600">{{ actionLabel($activity->action) }}</span>
                        <span class="text-slate-400 font-normal"> — {{ Str::limit($activity->document->title ?? $activity->document_title ?? '—', 40) }}</span>
                    </p>
                    <p class="text-[9px] text-slate-400 mt-0.5">
                        {{ $activity->user->full_name ?? 'Système' }}
                    </p>
                </div>
                <span class="text-[9px] text-slate-400 font-mono shrink-0">{{ $activity->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
(function () {
    const days     = @json($chart);
    const statuses = @json($statusBreakdown);
    const font     = 'inherit';
    const toneHex  = { emerald: '#10b981', amber: '#f59e0b', red: '#ef4444' };
    const nf       = new Intl.NumberFormat('fr-FR');

    // Regroupe les jours par mois pour la vue 12 mois
    function seriesFor(period) {
        const slice = days.slice(-period);
        if (period < 365) {
            return slice.map(d => ({ x: new Date(d.date + 'T00:00:00').getTime(), docs: d.documents, actions: d.actions }));
        }
        const months = {};
        slice.forEach(d => {
            const key = d.date.slice(0, 7);
            months[key] ??= { x: new Date(key + '-01T00:00:00').getTime(), docs: 0, actions: 0 };
            months[key].docs += d.documents;
            months[key].actions += d.actions;
        });
        return Object.values(months);
    }

    // Tendance : période en cours vs période précédente (documents ajoutés)
    function trend(period) {
        const cur  = days.slice(-period).reduce((s, d) => s + d.documents, 0);
        const prev = period < 365 ? days.slice(-2 * period, -period).reduce((s, d) => s + d.documents, 0) : null;
        return { cur, prev };
    }

    const activity = new ApexCharts(document.querySelector('#activity-chart'), {
        chart: {
            type: 'area', height: 300, fontFamily: font, parentHeightOffset: 0,
            toolbar: { show: false }, zoom: { enabled: false },
            animations: { enabled: true, easing: 'easeinout', speed: 700, dynamicAnimation: { speed: 450 } },
            dropShadow: { enabled: true, top: 6, left: 0, blur: 8, color: '#f97316', opacity: 0.12, enabledOnSeries: [0] },
        },
        series: [{ name: 'Documents ajoutés', data: [] }, { name: 'Actions', data: [] }],
        colors: ['#f97316', '#334155'],
        stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 5] },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: [0.45, 0.12], opacityTo: [0.02, 0], stops: [0, 90, 100] },
        },
        dataLabels: { enabled: false },
        markers: { size: 0, strokeWidth: 3, strokeColors: '#fff', hover: { size: 6 } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4, padding: { left: 12, right: 12 }, xaxis: { lines: { show: false } } },
        xaxis: {
            type: 'datetime',
            labels: { datetimeUTC: false, style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 } },
            axisBorder: { show: false }, axisTicks: { show: false },
            crosshairs: { stroke: { color: '#cbd5e1', width: 1, dashArray: 3 } },
            tooltip: { enabled: false },
        },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 }, formatter: v => nf.format(Math.round(v)) } },
        legend: { show: false },
        tooltip: { shared: true, intersect: false, theme: 'dark', x: { format: 'dd MMM yyyy' } },
    });
    activity.render();

    function setPeriod(period) {
        const data = seriesFor(period);
        const monthly = period >= 365;
        activity.updateOptions({
            tooltip: { x: { format: monthly ? 'MMMM yyyy' : 'dd MMM yyyy' } },
            markers: { size: period <= 7 ? 4 : 0 },
        }, false, false);
        activity.updateSeries([
            { name: 'Documents ajoutés', data: data.map(p => ({ x: p.x, y: p.docs })) },
            { name: 'Actions', data: data.map(p => ({ x: p.x, y: p.actions })) },
        ]);

        const t = trend(period);
        document.getElementById('chart-total-docs').textContent = nf.format(t.cur);
        document.getElementById('chart-total-actions').textContent = nf.format(data.reduce((s, p) => s + p.actions, 0));

        const badge = document.getElementById('chart-trend');
        if (t.prev === null || (t.prev === 0 && t.cur === 0)) {
            badge.className = 'hidden';
        } else {
            const pct = t.prev === 0 ? 100 : Math.round((t.cur - t.prev) / t.prev * 100);
            const up  = pct >= 0;
            badge.className = 'hidden sm:inline-flex self-start items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-black '
                + (up ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600');
            badge.innerHTML = '<i class="fa-solid fa-arrow-trend-' + (up ? 'up' : 'down') + '"></i> '
                + (up ? '+' : '') + pct + '% <span class="font-semibold opacity-70">vs période préc.</span>';
        }
    }

    window.gedDashboard = { setPeriod };
    setPeriod(30);

    // Donut des statuts
    const statusEl = document.querySelector('#status-chart');
    if (statusEl) {
        const palette = { 'Approuvé': '#10b981', 'En révision': '#3b82f6', 'Brouillon': '#94a3b8', 'Archivé': '#8b5cf6',
                          'En attente': '#f59e0b', 'Rejeté': '#ef4444' };
        const labels = Object.keys(statuses);
        new ApexCharts(statusEl, {
            chart: { type: 'donut', height: 300, fontFamily: font,
                     dropShadow: { enabled: true, top: 4, blur: 6, opacity: 0.08 } },
            series: Object.values(statuses),
            labels,
            colors: labels.map((l, i) => palette[l] ?? ['#f97316', '#0ea5e9', '#14b8a6'][i % 3]),
            stroke: { width: 3, colors: ['#fff'] },
            dataLabels: { enabled: false },
            legend: { position: 'bottom', fontSize: '11px', fontWeight: 600, labels: { colors: '#475569' },
                      markers: { width: 8, height: 8, radius: 3 }, itemMargin: { horizontal: 6, vertical: 3 } },
            plotOptions: { pie: { expandOnClick: true, donut: { size: '72%', labels: {
                show: true,
                value: { fontSize: '26px', fontWeight: 900, color: '#0f172a', offsetY: 4, formatter: v => nf.format(v) },
                name:  { fontSize: '10px', fontWeight: 800, color: '#94a3b8', offsetY: -8 },
                total: { show: true, label: 'TOTAL', fontSize: '10px', fontWeight: 800, color: '#94a3b8',
                         formatter: w => nf.format(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) },
            } } } },
            tooltip: { theme: 'dark', y: { formatter: v => nf.format(v) + ' document(s)' } },
            states: { hover: { filter: { type: 'darken', value: 0.9 } } },
        }).render();
    }

    // Jauges radiales du quota (stockage / utilisateurs)
    document.querySelectorAll('[id^="gauge-"]').forEach(el => {
        const color = toneHex[el.dataset.tone] ?? toneHex.emerald;
        const unlimited = el.dataset.unlimited === '1';
        new ApexCharts(el, {
            chart: { type: 'radialBar', height: 128, width: 128, sparkline: { enabled: true },
                     animations: { enabled: true, speed: 900 } },
            series: [Math.max(unlimited ? 100 : 2, parseFloat(el.dataset.pct))],
            colors: [color],
            fill: { type: 'gradient', gradient: { shade: 'light', type: 'horizontal', gradientToColors: [color],
                    opacityFrom: unlimited ? 0.35 : 0.75, opacityTo: unlimited ? 0.35 : 1, stops: [0, 100] } },
            stroke: { lineCap: 'round' },
            plotOptions: { radialBar: {
                hollow: { size: '62%' },
                track: { background: '#f1f5f9', strokeWidth: '100%' },
                dataLabels: { show: false },
            } },
        }).render();
    });
})();
</script>
@endsection
