@extends('layouts.app')

@section('content')
@php
    $me = auth()->user();
    $retention = $info->retention();
    $accessRules = $info->departments();
    $publicSource = $info->publicSource();
    $children = $category->children;
    $total = $node->total_count ?? 0;
    $direct = $node->documents_count ?? 0;
    $explorerUrl = route('documents.index', ['category' => $category->id]);
    $card = 'bg-white rounded-2xl border border-slate-100 shadow-sm';
    $statusClass = fn ($status) => match ($status) {
        'approved' => 'bg-green-50 text-green-600',
        'review'   => 'bg-blue-50 text-blue-600',
        'archived' => 'bg-slate-100 text-slate-400',
        default    => 'bg-amber-50 text-amber-600',
    };
@endphp
<div class="space-y-6">

    {{-- ===== EN-TÊTE ===== --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div class="min-w-0">
            <nav class="flex items-center flex-wrap gap-1 text-xs font-medium text-slate-400 mb-2" aria-label="Fil d'Ariane">
                <a href="{{ route('categories.index') }}" class="hover:text-orange-600 transition-colors">Catégories</a>
                @foreach($path as $crumb)
                @continue($loop->last)
                <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 mx-0.5"></i>
                <a href="{{ route('categories.show', $crumb) }}" class="hover:text-orange-600 transition-colors">{{ $crumb->name }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-folder text-amber-400 text-2xl"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight truncate" title="{{ $category->name }}">{{ $category->name }}</h1>
                    <p class="text-sm text-slate-500 mt-0.5 {{ $category->description ? '' : 'italic text-slate-300' }}">
                        {{ $category->description ?: 'Aucune description' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($me->hasRole('admin'))
            <a href="{{ route('categories.edit', $category) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-sliders text-[10px]"></i> Paramètres
            </a>
            @endif
            <a href="{{ $explorerUrl }}"
               class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
                <i class="fa-solid fa-folder-open text-[11px]"></i> Ouvrir le dossier
            </a>
        </div>
    </div>

    {{-- ===== CHIFFRES CLÉS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="{{ $card }} p-4">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Documents</p>
            <p class="text-2xl font-black text-slate-900 mt-1.5 leading-none">{{ number_format($total, 0, ',', ' ') }}</p>
            <p class="text-[11px] text-slate-400 mt-1.5">
                @if($children->isNotEmpty()){{ $direct }} ici · {{ $total - $direct }} dans les sous-dossiers @else Visibles par vous @endif
            </p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sous-dossiers</p>
            <p class="text-2xl font-black text-slate-900 mt-1.5 leading-none">{{ $children->count() }}</p>
            <p class="text-[11px] text-slate-400 mt-1.5">{{ $children->isEmpty() ? 'Aucun' : 'Directement dans ce dossier' }}</p>
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Conservation</p>
            @if($retention['years'])
            <p class="text-2xl font-black text-slate-900 mt-1.5 leading-none">{{ $retention['years'] }} <span class="text-sm font-bold text-slate-400">an{{ $retention['years'] > 1 ? 's' : '' }}</span></p>
            <p class="text-[11px] text-slate-400 mt-1.5 truncate">{{ $retention['inherited'] ? 'Héritée de « ' . $retention['source']->name . ' »' : 'Définie sur ce dossier' }}</p>
            @else
            <p class="text-2xl font-black text-slate-300 mt-1.5 leading-none">—</p>
            <p class="text-[11px] text-slate-400 mt-1.5">Non définie</p>
            @endif
        </div>
        <div class="{{ $card }} p-4">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Accès</p>
            @if($publicSource)
            <p class="text-2xl font-black text-emerald-600 mt-1.5 leading-none">Public</p>
            <p class="text-[11px] text-slate-400 mt-1.5">Consultable par tous</p>
            @elseif($accessRules->isNotEmpty())
            <p class="text-2xl font-black text-slate-900 mt-1.5 leading-none">{{ $accessRules->count() }} <span class="text-sm font-bold text-slate-400">service{{ $accessRules->count() > 1 ? 's' : '' }}</span></p>
            <p class="text-[11px] text-slate-400 mt-1.5">{{ $accessRules->where('level', 'edit')->count() }} en modification</p>
            @else
            <p class="text-2xl font-black text-slate-500 mt-1.5 leading-none">Privé</p>
            <p class="text-[11px] text-slate-400 mt-1.5">Aucun service autorisé</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- ===== CONTENU ===== --}}
        <div class="lg:col-span-2 space-y-6">

            @if($children->isNotEmpty())
            <section class="{{ $card }} overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
                    <h2 class="text-sm font-black text-slate-900">Sous-dossiers</h2>
                    <span class="text-[11px] font-bold text-slate-400">{{ $children->count() }}</span>
                </div>
                <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($children as $child)
                    <a href="{{ route('categories.show', $child) }}"
                       class="group flex items-center gap-3 rounded-xl px-3 py-2.5 hover:bg-slate-50 transition-colors">
                        <span class="w-9 h-9 rounded-lg bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center shrink-0 transition-colors">
                            <i class="fa-solid fa-folder text-amber-400"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[13px] font-bold text-slate-700 group-hover:text-orange-700 truncate">{{ $child->name }}</span>
                            <span class="block text-[11px] text-slate-400" title="Sous-dossiers compris">{{ $child->total_count }} doc(s)@if($child->children->isNotEmpty()) · {{ $child->children->count() }} dossier(s)@endif</span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-200 group-hover:text-orange-400"></i>
                    </a>
                    @endforeach
                </div>
            </section>
            @endif

            <section class="{{ $card }} overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
                    <h2 class="text-sm font-black text-slate-900">Derniers documents</h2>
                    @if($total)
                    <a href="{{ $explorerUrl }}" class="text-[11px] font-bold text-orange-600 hover:text-orange-700">
                        Voir les {{ $total }} <i class="fa-solid fa-arrow-right text-[9px] ml-0.5"></i>
                    </a>
                    @endif
                </div>

                @if($recentDocuments->isEmpty())
                <div class="flex flex-col items-center text-center py-12 px-6">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-folder-open text-amber-300 text-2xl"></i>
                    </div>
                    <p class="text-sm font-black text-slate-700">Ce dossier est vide</p>
                    <p class="text-xs text-slate-400 mt-1">Ouvrez-le pour y importer des fichiers ou créer un document.</p>
                    <a href="{{ $explorerUrl }}" class="mt-4 inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2 rounded-xl transition-all">
                        <i class="fa-solid fa-folder-open text-[10px]"></i> Ouvrir le dossier
                    </a>
                </div>
                @else
                <ul class="divide-y divide-slate-50">
                    @foreach($recentDocuments as $doc)
                    <li>
                        <a href="{{ route('documents.show', $doc) }}" class="group flex items-center gap-3 px-5 py-3 hover:bg-slate-50/70 transition-colors">
                            <span class="w-9 h-9 rounded-lg bg-slate-50 group-hover:bg-white flex items-center justify-center shrink-0 transition-colors">
                                <x-file-icon :document="$doc" class="text-sm" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[13px] font-bold text-slate-800 group-hover:text-orange-700 truncate transition-colors">{{ $doc->title }}</span>
                                <span class="block text-[11px] text-slate-400 truncate">
                                    <span class="font-mono">{{ $doc->reference }}</span>
                                    · {{ $doc->creator?->full_name ?? '—' }}
                                    @if($doc->category_id !== $category->id) · <i class="fa-solid fa-folder text-amber-300 text-[9px]"></i> {{ $doc->category?->name }}@endif
                                </span>
                            </span>
                            <span class="hidden sm:block text-[11px] text-slate-400 shrink-0" title="{{ $doc->created_at->format('d/m/Y H:i') }}">{{ $doc->created_at->diffForHumans() }}</span>
                            <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0 {{ $statusClass($doc->status) }}">{{ statusLabel($doc->status) }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endif
            </section>
        </div>

        {{-- ===== RÈGLES ===== --}}
        <aside class="{{ $card }} divide-y divide-slate-50">

            {{-- Conservation --}}
            <div class="p-5">
                <h2 class="flex items-center gap-2 text-sm font-black text-slate-900 mb-3">
                    <i class="fa-solid fa-hourglass-half text-slate-300 text-xs"></i> Conservation
                </h2>
                @if($retention['years'])
                <dl class="space-y-2.5 text-xs">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Durée</dt>
                        <dd class="font-bold text-slate-700 text-right">{{ $retention['years'] }} an{{ $retention['years'] > 1 ? 's' : '' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">À partir de</dt>
                        <dd class="font-bold text-slate-700 text-right">{{ \App\Models\Category::RETENTION_TRIGGERS[$retention['trigger']] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400 shrink-0">Ensuite</dt>
                        <dd class="font-bold text-slate-700 text-right">{{ \App\Models\Category::FINAL_DISPOSITIONS[$retention['disposition']] ?? '—' }}</dd>
                    </div>
                </dl>
                @if($retention['inherited'])
                <a href="{{ route('categories.show', $retention['source']) }}"
                   class="mt-3 flex items-center gap-2 text-[11px] text-slate-500 bg-slate-50 hover:bg-orange-50 hover:text-orange-700 rounded-lg px-3 py-2 transition-colors">
                    <i class="fa-solid fa-arrow-turn-up text-slate-300 text-[10px]"></i>
                    <span class="truncate">Héritée de « {{ $retention['source']->name }} »</span>
                </a>
                @endif
                @else
                <p class="text-xs text-slate-400 leading-relaxed">Aucune durée définie sur ce dossier ni sur ses parents : les documents sont conservés sans échéance.</p>
                @endif
            </div>

            {{-- Accès --}}
            <div class="p-5">
                <h2 class="flex items-center gap-2 text-sm font-black text-slate-900 mb-3">
                    <i class="fa-solid fa-user-group text-slate-300 text-xs"></i> Qui a accès
                </h2>
                @if($publicSource)
                <div class="flex items-start gap-2 text-[11px] text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2 mb-3">
                    <i class="fa-solid fa-globe mt-0.5"></i>
                    <span>Tous les utilisateurs peuvent consulter les documents non confidentiels{{ $publicSource->id !== $category->id ? ' (ouvert par « ' . $publicSource->name . ' »)' : '' }}.</span>
                </div>
                @endif
                @if($accessRules->isNotEmpty())
                <ul class="space-y-2">
                    @foreach($accessRules as $rule)
                    <li class="flex items-center gap-2.5">
                        <span class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500 shrink-0">
                            {{ mb_strtoupper(mb_substr($rule['name'], 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-bold text-slate-700 truncate">{{ $rule['name'] }}</span>
                            @if($rule['inherited'])
                            <span class="block text-[10px] text-slate-400 truncate">via « {{ $rule['source']->name }} »</span>
                            @endif
                        </span>
                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-1 rounded-md shrink-0 {{ $rule['level'] === 'edit' ? 'bg-orange-50 text-orange-600' : 'bg-slate-100 text-slate-500' }}">
                            {{ $rule['level'] === 'edit' ? 'Modification' : 'Lecture' }}
                        </span>
                    </li>
                    @endforeach
                </ul>
                @elseif(!$publicSource)
                <p class="text-xs text-slate-400 leading-relaxed">Aucun service n'y a accès : chacun ne voit que ses propres documents et ceux partagés avec lui. Les administrateurs voient tout.</p>
                @endif
                @if($me->hasRole('admin'))
                <a href="{{ route('categories.edit', $category) }}" class="mt-3 inline-block text-[11px] font-bold text-orange-600 hover:text-orange-700">Gérer les accès <i class="fa-solid fa-arrow-right text-[9px]"></i></a>
                @endif
            </div>

            {{-- Informations --}}
            <div class="p-5">
                <h2 class="flex items-center gap-2 text-sm font-black text-slate-900 mb-3">
                    <i class="fa-solid fa-circle-info text-slate-300 text-xs"></i> Informations
                </h2>
                <dl class="space-y-2.5 text-xs">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Créé par</dt>
                        <dd class="font-bold text-slate-700 text-right truncate">{{ $category->creator?->full_name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Créé le</dt>
                        <dd class="font-bold text-slate-700 text-right">{{ $category->created_at?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-400">Identifiant</dt>
                        <dd class="font-mono text-[11px] text-slate-500 text-right truncate">{{ $category->slug }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>
</div>
@endsection
