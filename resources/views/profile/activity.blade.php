@extends('layouts.app')

@section('content')
@php
    $actionConfig = [
        'created'    => ['color' => 'bg-green-50 text-green-700 ring-1 ring-green-200',   'icon' => 'fa-plus',              'dot' => 'bg-green-500',   'bg' => 'bg-green-100'],
        'updated'    => ['color' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',      'icon' => 'fa-pen',               'dot' => 'bg-blue-500',    'bg' => 'bg-blue-100'],
        'viewed'     => ['color' => 'bg-slate-100 text-slate-500',                        'icon' => 'fa-eye',               'dot' => 'bg-slate-400',   'bg' => 'bg-slate-100'],
        'downloaded' => ['color' => 'bg-purple-50 text-purple-700 ring-1 ring-purple-200','icon' => 'fa-download',          'dot' => 'bg-purple-500',  'bg' => 'bg-purple-100'],
        'archived'   => ['color' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',   'icon' => 'fa-box-archive',       'dot' => 'bg-amber-500',   'bg' => 'bg-amber-100'],
        'deleted'    => ['color' => 'bg-red-50 text-red-700 ring-1 ring-red-200',         'icon' => 'fa-trash-can',         'dot' => 'bg-red-500',     'bg' => 'bg-red-100'],
        'approved'   => ['color' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200','icon' => 'fa-circle-check',  'dot' => 'bg-emerald-500', 'bg' => 'bg-emerald-100'],
        'rejected'   => ['color' => 'bg-red-50 text-red-700 ring-1 ring-red-200',         'icon' => 'fa-circle-xmark',     'dot' => 'bg-red-500',     'bg' => 'bg-red-100'],
        'signed'     => ['color' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200','icon' => 'fa-signature',        'dot' => 'bg-indigo-500',  'bg' => 'bg-indigo-100'],
        'shared'     => ['color' => 'bg-teal-50 text-teal-700 ring-1 ring-teal-200',      'icon' => 'fa-share-nodes',      'dot' => 'bg-teal-500',    'bg' => 'bg-teal-100'],
        'commented'  => ['color' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',         'icon' => 'fa-comment',          'dot' => 'bg-sky-500',     'bg' => 'bg-sky-100'],
        'locked'     => ['color' => 'bg-slate-100 text-slate-600',                        'icon' => 'fa-lock',             'dot' => 'bg-slate-500',   'bg' => 'bg-slate-100'],
        'unlocked'   => ['color' => 'bg-slate-100 text-slate-600',                        'icon' => 'fa-lock-open',        'dot' => 'bg-slate-400',   'bg' => 'bg-slate-100'],
    ];
    $defaultConfig = ['color' => 'bg-slate-100 text-slate-500', 'icon' => 'fa-bolt', 'dot' => 'bg-slate-400', 'bg' => 'bg-slate-100'];

    // Stats rapides
    $totalActions   = $activities->total();
    $actionCounts   = [];
    if ($activities->count()) {
        foreach ($activities->items() as $log) {
            $actionCounts[$log->action] = ($actionCounts[$log->action] ?? 0) + 1;
        }
        arsort($actionCounts);
    }
@endphp

<div class="space-y-6">

    {{-- BREADCRUMB + HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[11px] text-slate-400 font-semibold mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-orange-500 transition-colors">
                    <i class="fa-solid fa-house text-[10px]"></i>
                </a>
                <i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>
                <a href="{{ route('profile.show') }}" class="hover:text-orange-500 transition-colors">Mon profil</a>
                <i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>
                <span class="text-slate-600">Mon activité</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Mon activité</h1>
            <p class="text-xs text-slate-400 font-medium mt-1.5">
                {{ number_format($activities->total()) }} action(s) enregistrée(s) dans le journal d'audit
            </p>
        </div>
        <a href="{{ route('profile.show') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour au profil
        </a>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @php
            $kpis = [
                ['label' => 'Total actions',  'value' => $activities->total(),                                                                    'icon' => 'fa-bolt',         'color' => 'bg-orange-50 text-orange-600'],
                ['label' => 'Cette page',     'value' => $activities->count(),                                                                    'icon' => 'fa-list',         'color' => 'bg-blue-50 text-blue-600'],
                ['label' => 'Action la + fréquente', 'value' => $actionCounts ? actionLabel(array_key_first($actionCounts)) : '—',               'icon' => 'fa-chart-simple', 'color' => 'bg-purple-50 text-purple-600', 'text' => true],
                ['label' => 'Dernière action','value' => $activities->count() ? $activities->first()->created_at->diffForHumans() : '—',          'icon' => 'fa-clock',        'color' => 'bg-green-50 text-green-600',  'text' => true],
            ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ $kpi['label'] }}</p>
                    @if(!empty($kpi['text']))
                    <p class="text-sm font-black text-slate-900 leading-tight truncate">{{ $kpi['value'] }}</p>
                    @else
                    <p class="text-2xl font-black text-slate-900 leading-none">{{ $kpi['value'] }}</p>
                    @endif
                </div>
                <div class="w-9 h-9 rounded-xl {{ $kpi['color'] }} flex items-center justify-center shrink-0">
                    <i class="fa-solid {{ $kpi['icon'] }} text-sm"></i>
                </div>
            </div>
            <div class="absolute -bottom-3 -right-3 w-14 h-14 rounded-full opacity-30 {{ $kpi['color'] }} group-hover:opacity-50 transition-opacity"></div>
        </div>
        @endforeach
    </div>

    {{-- CONTENU PRINCIPAL --}}
    @if($activities->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-20 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-5">
            <i class="fa-solid fa-clock-rotate-left text-slate-300 text-2xl"></i>
        </div>
        <p class="text-sm font-black text-slate-400">Aucune activité enregistrée</p>
        <p class="text-xs text-slate-300 mt-1">Vos actions sur les documents apparaîtront ici</p>
        <a href="{{ route('documents.index') }}"
           class="mt-5 inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all active:scale-95">
            <i class="fa-solid fa-file-lines text-[10px]"></i> Voir les documents
        </a>
    </div>
    @else

    {{-- VUE DESKTOP : Timeline --}}
    <div class="hidden md:block bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- Header table --}}
        <div class="grid grid-cols-12 gap-4 px-6 py-3.5 bg-slate-50 border-b border-slate-100">
            <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Action</div>
            <div class="col-span-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Document</div>
            <div class="col-span-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Description</div>
            <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Date & Heure</div>
        </div>

        <div class="divide-y divide-slate-50">
            @foreach($activities as $log)
            @php $cfg = $actionConfig[$log->action] ?? $defaultConfig; @endphp
            <div class="group grid grid-cols-12 gap-4 px-6 py-4 items-center hover:bg-slate-50/60 transition-colors">

                {{-- Action badge --}}
                <div class="col-span-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-wider {{ $cfg['color'] }}">
                        <i class="fa-solid {{ $cfg['icon'] }} text-[8px]"></i>
                        {{ actionLabel($log->action) }}
                    </span>
                </div>

                {{-- Document --}}
                <div class="col-span-4 min-w-0">
                    @if($log->document)
                    <a href="{{ route('documents.show', $log->document) }}"
                       class="flex items-center gap-2.5 group/doc">
                        <div class="w-7 h-7 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 group-hover/doc:bg-orange-600 transition-colors">
                            <i class="fa-solid fa-file-word text-orange-500 text-[9px] group-hover/doc:text-white transition-colors"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 group-hover/doc:text-orange-600 transition-colors truncate leading-tight">
                                {{ $log->document->title }}
                            </p>
                            <p class="text-[9px] font-mono text-slate-400 mt-0.5">{{ $log->document->reference }}</p>
                        </div>
                    </a>
                    @else
                    <span class="text-xs text-slate-300 font-medium">Document supprimé</span>
                    @endif
                </div>

                {{-- Description --}}
                <div class="col-span-4">
                    <p class="text-xs text-slate-500 truncate leading-relaxed">
                        {{ $log->description ?: '—' }}
                    </p>
                    @if($log->ip_address)
                    <p class="text-[9px] text-slate-300 font-mono mt-0.5">
                        <i class="fa-solid fa-location-dot text-[8px] mr-0.5"></i>{{ $log->ip_address }}
                    </p>
                    @endif
                </div>

                {{-- Date --}}
                <div class="col-span-2 text-right">
                    <p class="text-xs font-bold text-slate-700">{{ $log->created_at->format('d/m/Y') }}</p>
                    <p class="text-[9px] text-slate-400 font-mono mt-0.5">{{ $log->created_at->format('H:i:s') }}</p>
                    <p class="text-[9px] text-slate-300 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- VUE MOBILE : Timeline verticale --}}
    <div class="md:hidden space-y-0 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-4 py-3.5 border-b border-slate-50 bg-slate-50/60">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Historique des actions</p>
        </div>
        <div class="relative">
            {{-- Ligne verticale --}}
            <div class="absolute left-[27px] top-0 bottom-0 w-px bg-slate-100"></div>

            <div class="divide-y divide-slate-50">
                @foreach($activities as $log)
                @php $cfg = $actionConfig[$log->action] ?? $defaultConfig; @endphp
                <div class="flex items-start gap-3 px-4 py-4 hover:bg-slate-50/60 transition-colors">
                    {{-- Dot sur la timeline --}}
                    <div class="relative z-10 w-7 h-7 rounded-xl {{ $cfg['bg'] }} flex items-center justify-center shrink-0 mt-0.5 border-2 border-white shadow-sm">
                        <i class="fa-solid {{ $cfg['icon'] }} text-[9px]
                            {{ str_replace(['bg-', '-100'], ['text-', '-600'], $cfg['bg']) }}"></i>
                    </div>

                    <div class="flex-1 min-w-0 pt-0.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider {{ $cfg['color'] }}">
                                {{ actionLabel($log->action) }}
                            </span>
                            <span class="text-[9px] text-slate-400 font-mono shrink-0">{{ $log->created_at->format('d/m H:i') }}</span>
                        </div>

                        @if($log->document)
                        <a href="{{ route('documents.show', $log->document) }}"
                           class="flex items-center gap-1.5 mt-1.5 group/doc">
                            <i class="fa-solid fa-file-word text-orange-400 text-[9px] shrink-0"></i>
                            <span class="text-xs font-semibold text-slate-700 group-hover/doc:text-orange-600 transition-colors truncate">
                                {{ $log->document->title }}
                            </span>
                        </a>
                        @endif

                        @if($log->description)
                        <p class="text-[10px] text-slate-400 mt-1 leading-relaxed truncate">{{ $log->description }}</p>
                        @endif

                        <p class="text-[9px] text-slate-300 mt-1">{{ $log->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- PAGINATION --}}
    @if($activities->hasPages())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
            {{ $activities->firstItem() }}–{{ $activities->lastItem() }}
            <span class="text-slate-300">sur</span>
            {{ number_format($activities->total()) }} actions
        </p>
        {{ $activities->links() }}
    </div>
    @endif

    @endif

    {{-- LIENS RAPIDES --}}
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-5">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Accès rapide</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <a href="{{ route('documents.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 hover:bg-orange-50 hover:text-orange-700 text-slate-600 text-xs font-bold transition-all">
                <i class="fa-solid fa-file-lines text-orange-400 text-sm"></i>
                Mes documents
            </a>
            <a href="{{ route('profile.sessions') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-600 text-xs font-bold transition-all">
                <i class="fa-solid fa-desktop text-blue-400 text-sm"></i>
                Sessions actives
            </a>
            <a href="{{ route('profile.show') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 hover:bg-purple-50 hover:text-purple-700 text-slate-600 text-xs font-bold transition-all">
                <i class="fa-solid fa-circle-user text-purple-400 text-sm"></i>
                Mon profil
            </a>
        </div>
    </div>

</div>
@endsection
