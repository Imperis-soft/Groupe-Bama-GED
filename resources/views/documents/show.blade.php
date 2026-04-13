@extends('layouts.app')

@section('content')
@php
    $isFav = $document->isFavoritedBy();
    $lock  = $document->lock;
    $isLockedByOther = $lock && !$lock->isExpired() && $lock->locked_by !== auth()->id();
    $isLockedByMe    = $lock && !$lock->isExpired() && $lock->locked_by === auth()->id();
    $statusColors = [
        'approved' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'review'   => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
        'archived' => 'bg-slate-100 text-slate-500 ring-1 ring-slate-200',
        'draft'    => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    ];
    $statusIcons = [
        'approved' => 'fa-circle-check',
        'review'   => 'fa-clock',
        'archived' => 'fa-box-archive',
        'draft'    => 'fa-pen-to-square',
    ];
    $sc = $statusColors[$document->status] ?? 'bg-slate-100 text-slate-500';
    $si = $statusIcons[$document->status] ?? 'fa-file';
@endphp

<div x-data="{
    tab: 'overview',
    favLoading: false,
    isFav: {{ $isFav ? 'true' : 'false' }},
    lockStatus: '{{ $isLockedByMe ? 'mine' : ($isLockedByOther ? 'other' : 'free') }}',
    lockBy: '{{ $isLockedByOther ? ($lock->lockedBy->full_name ?? '') : '' }}',
    sigModal: false,
    shareModal: false,
    archiveModal: false,
    uploadModal: false,
    commentContent: '',
    replyTo: null,
    replyContent: '',

    async toggleFav() {
        this.favLoading = true;
        const res = await fetch('{{ route('documents.favorite', $document) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
        });
        const data = await res.json();
        this.isFav = data.favorited;
        this.favLoading = false;
    },

    async acquireLock() {
        const res = await fetch('{{ route('documents.lock.acquire', $document) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' }
        });
        if (res.ok) { this.lockStatus = 'mine'; }
        else { const d = await res.json(); alert('Verrouill� par ' + d.locked_by); }
    },

    async releaseLock() {
        await fetch('{{ route('documents.lock.release', $document) }}', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        this.lockStatus = 'free';
    }
}">

{{-- --------------------------------------------------------------
     BREADCRUMB
-------------------------------------------------------------- --}}
<nav class="flex items-center gap-2 text-[11px] text-slate-400 font-semibold mb-5">
    <a href="{{ route('dashboard') }}" class="hover:text-orange-500 transition-colors">
        <i class="fa-solid fa-house text-[10px]"></i>
    </a>
    <i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>
    <a href="{{ route('documents.index') }}" class="hover:text-orange-500 transition-colors">Documents</a>
    <i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>
    <span class="text-slate-600 truncate max-w-[200px]">{{ $document->title }}</span>
</nav>

{{-- --------------------------------------------------------------
     HERO HEADER
-------------------------------------------------------------- --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm mb-5 overflow-hidden">
    {{-- Top accent bar --}}
    <div class="h-1 w-full {{ $document->status === 'approved' ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : ($document->status === 'review' ? 'bg-gradient-to-r from-blue-400 to-blue-500' : ($document->status === 'archived' ? 'bg-slate-200' : 'bg-gradient-to-r from-amber-400 to-orange-500')) }}"></div>

    <div class="p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-start gap-5">

            {{-- Icon + Info --}}
            <div class="flex items-start gap-4 flex-1 min-w-0">
                <div class="relative shrink-0">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-50 to-orange-100 flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-file-word text-orange-500 text-2xl"></i>
                    </div>
                    @if($document->is_confidential)
                    <div class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center shadow">
                        <i class="fa-solid fa-lock text-white text-[8px]"></i>
                    </div>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start gap-3 flex-wrap">
                        <h1 class="text-xl font-black text-slate-900 leading-tight">{{ $document->title }}</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest {{ $sc }}">
                            <i class="fa-solid {{ $si }} text-[9px]"></i>
                            {{ $document->status }}
                        </span>
                        @if($document->is_confidential)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 ring-1 ring-red-200 rounded-lg text-[10px] font-black uppercase tracking-widest">
                            <i class="fa-solid fa-shield-halved text-[9px]"></i> Confidentiel
                        </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        <span class="font-mono text-[11px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-lg tracking-wider">
                            {{ $document->reference }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">
                            <i class="fa-solid fa-code-branch text-[9px] mr-1"></i>v{{ $document->version }}
                        </span>
                        @if($document->category)
                        <span class="text-[11px] text-slate-400 font-medium">
                            <i class="fa-solid fa-folder text-[9px] mr-1 text-orange-400"></i>{{ $document->category->name }}
                        </span>
                        @endif
                        <span class="text-[11px] text-slate-400 font-medium">
                            <i class="fa-regular fa-calendar text-[9px] mr-1"></i>{{ $document->created_at->format('d/m/Y') }}
                        </span>
                        @if($document->creator)
                        <span class="text-[11px] text-slate-400 font-medium">
                            <i class="fa-solid fa-user text-[9px] mr-1"></i>{{ $document->creator->full_name ?? $document->creator->name }}
                        </span>
                        @endif
                    </div>

                    {{-- Tags --}}
                    @if($document->tags && count($document->tags))
                    <div class="flex items-center gap-1.5 mt-2.5 flex-wrap">
                        @foreach($document->tags as $tag)
                        <span class="px-2 py-0.5 bg-orange-50 text-orange-600 rounded-md text-[10px] font-bold">
                            <i class="fa-solid fa-tag text-[8px] mr-0.5"></i>{{ $tag }}
                        </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center gap-2 flex-wrap lg:shrink-0">

                {{-- Favori --}}
                <button @click="toggleFav()" :disabled="favLoading"
                    class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all"
                    :class="isFav ? 'bg-amber-50 border-amber-200 text-amber-500' : 'bg-white border-slate-200 text-slate-400 hover:border-amber-200 hover:text-amber-400'"
                    title="Favori">
                    <i class="fa-star text-sm" :class="isFav ? 'fa-solid' : 'fa-regular'"></i>
                </button>

                {{-- Lock --}}
                <button
                    @click="lockStatus === 'mine' ? releaseLock() : acquireLock()"
                    :disabled="lockStatus === 'other'"
                    class="w-9 h-9 flex items-center justify-center rounded-xl border transition-all"
                    :class="{
                        'bg-green-50 border-green-200 text-green-600': lockStatus === 'mine',
                        'bg-red-50 border-red-200 text-red-400 cursor-not-allowed': lockStatus === 'other',
                        'bg-white border-slate-200 text-slate-400 hover:border-slate-300': lockStatus === 'free'
                    }"
                    :title="lockStatus === 'mine' ? 'D�verrouiller' : (lockStatus === 'other' ? 'Verrouill� par ' + lockBy : 'Verrouiller')">
                    <i class="fa-solid text-sm" :class="lockStatus === 'free' ? 'fa-lock-open' : 'fa-lock'"></i>
                </button>

                {{-- Télécharger --}}
                <a href="{{ route('documents.download', $document) }}"
                   class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-600 text-xs font-bold px-3.5 py-2 rounded-xl transition-all shadow-sm">
                    <i class="fa-solid fa-download text-[10px]"></i>
                    <span class="hidden sm:inline">Télécharger</span>
                </a>

                {{-- Partager --}}
                <button @click="shareModal = true"
                    class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-600 text-xs font-bold px-3.5 py-2 rounded-xl transition-all shadow-sm">
                    <i class="fa-solid fa-share-nodes text-[10px]"></i>
                    <span class="hidden sm:inline">Partager</span>
                </button>

                {{-- éditer --}}
                @if($document->canEdit())
                <a href="{{ route('documents.edit', $document) }}"
                   class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-600 text-xs font-bold px-3.5 py-2 rounded-xl transition-all shadow-sm">
                    <i class="fa-solid fa-pen text-[10px]"></i>
                    <span class="hidden sm:inline">Modifier</span>
                </a>
                <a href="{{ route('documents.edit-online', $document) }}"
                   class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black px-3.5 py-2 rounded-xl transition-all shadow-lg shadow-orange-200">
                    <i class="fa-solid fa-pen-nib text-[10px]"></i>
                    <span class="hidden sm:inline">éditer en ligne</span>
                </a>
                @endif

                {{-- Menu contextuel --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false"
                        class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 transition-all shadow-sm">
                        <i class="fa-solid fa-ellipsis-vertical text-sm"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 top-full mt-1.5 w-52 bg-white border border-slate-100 rounded-2xl shadow-xl z-50 py-1.5 overflow-hidden">
                        <a href="{{ route('documents.preview', $document) }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fa-solid fa-eye w-4 text-slate-400"></i> Prévisualiser
                        </a>
                        <a href="{{ route('documents.versions', $document) }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fa-solid fa-code-branch w-4 text-slate-400"></i> Versions
                        </a>
                        <a href="{{ route('documents.signatures', $document) }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fa-solid fa-signature w-4 text-slate-400"></i> Signatures
                        </a>
                        <a href="{{ route('documents.approval', $document) }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fa-solid fa-list-check w-4 text-slate-400"></i> Workflow
                        </a>
                        <a href="{{ route('documents.audit', $document) }}"
                           class="flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fa-solid fa-clock-rotate-left w-4 text-slate-400"></i> Journal d'audit
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <button @click="archiveModal = true; open = false"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-amber-600 hover:bg-amber-50 transition-colors">
                            <i class="fa-solid fa-box-archive w-4"></i> Archiver
                        </button>
                        @if(auth()->user()->hasRole('admin'))
                        <form action="{{ route('documents.destroy', $document) }}" method="POST"
                              onsubmit="return confirm('Supprimer ce document ?');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                                <i class="fa-solid fa-trash-can w-4"></i> Supprimer
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Lock warning banner --}}
        <div x-show="lockStatus === 'other'" x-cloak
             class="mt-4 flex items-center gap-3 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
            <i class="fa-solid fa-lock text-red-500 text-sm"></i>
            <p class="text-xs font-bold text-red-700">
                Ce document est verrouill� par <span x-text="lockBy" class="underline"></span> � �dition impossible.
            </p>
        </div>
        <div x-show="lockStatus === 'mine'" x-cloak
             class="mt-4 flex items-center gap-3 bg-green-50 border border-green-100 rounded-xl px-4 py-3">
            <i class="fa-solid fa-lock text-green-500 text-sm"></i>
            <p class="text-xs font-bold text-green-700">Vous avez verrouill� ce document pour �dition.</p>
            <button @click="releaseLock()" class="ml-auto text-[10px] font-black text-green-700 hover:text-green-900 underline">Lib�rer</button>
        </div>
    </div>
</div>


{{-- --------------------------------------------------------------
     STATS RAPIDES
-------------------------------------------------------------- --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-eye text-blue-500 text-sm"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Consultations</p>
            <p class="text-lg font-black text-slate-800 leading-none mt-0.5">
                {{ $document->auditLogs->where('action', 'viewed')->count() }}
            </p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-code-branch text-purple-500 text-sm"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Versions</p>
            <p class="text-lg font-black text-slate-800 leading-none mt-0.5">{{ $document->versions->count() }}</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-comments text-orange-500 text-sm"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Commentaires</p>
            <p class="text-lg font-black text-slate-800 leading-none mt-0.5">{{ $document->comments->count() }}</p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-signature text-emerald-500 text-sm"></i>
        </div>
        <div>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Signatures</p>
            <p class="text-lg font-black text-slate-800 leading-none mt-0.5">{{ $document->signatures->count() }}</p>
        </div>
    </div>
</div>


{{-- TABS NAV --}}
<div class="flex items-center gap-1 bg-white border border-slate-100 rounded-2xl p-1 shadow-sm mb-5 overflow-x-auto">
    @foreach([
        ['overview',   'fa-circle-info',        'Apercu'],
        ['comments',   'fa-comments',            'Commentaires'],
        ['approval',   'fa-list-check',          'Approbation'],
        ['signatures', 'fa-signature',           'Signatures'],
        ['shares',     'fa-share-nodes',         'Partages'],
        ['versions',   'fa-code-branch',         'Versions'],
        ['audit',      'fa-clock-rotate-left',   'Historique'],
    ] as [$key, $icon, $label])
    <button @click="tab = '{{ $key }}'"
        :class="tab === '{{ $key }}' ? 'bg-slate-900 text-white shadow' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-50'"
        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-[11px] font-bold whitespace-nowrap transition-all">
        <i class="fa-solid {{ $icon }} text-[10px]"></i>
        <span class="hidden sm:inline">{{ $label }}</span>
    </button>
    @endforeach
</div>

{{-- TAB: APERCU --}}
<div x-show="tab === 'overview'" class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Informations</h2>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Categorie</p><p class="text-sm font-bold text-slate-800">{{ $document->category?->name ?? 'General' }}</p></div>
                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Version</p><p class="text-sm font-bold text-slate-800">{{ $document->version }}</p></div>
                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Cree le</p><p class="text-sm font-bold text-slate-800">{{ $document->created_at->format('d/m/Y H:i') }}</p></div>
                <div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Modifie le</p><p class="text-sm font-bold text-slate-800">{{ $document->updated_at->diffForHumans() }}</p></div>
                @if($document->expires_at)<div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Expiration</p><p class="text-sm font-bold {{ $document->expires_at->isPast() ? 'text-red-600' : 'text-slate-800' }}">{{ $document->expires_at->format('d/m/Y') }}</p></div>@endif
                @if($document->retention_years)<div><p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Retention</p><p class="text-sm font-bold text-slate-800">{{ $document->retention_years }} ans</p></div>@endif
            </div>
        </div>
        @if($document->tags && count($document->tags))
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Tags</h2>
            <div class="flex flex-wrap gap-2">
                @foreach($document->tags as $tag)
                <span class="px-3 py-1.5 bg-orange-50 text-orange-600 rounded-xl text-xs font-bold"><i class="fa-solid fa-tag text-[8px] mr-1"></i>{{ trim($tag) }}</span>
                @endforeach
            </div>
        </div>
        @endif
        @if($document->metadata)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Metadonnees</h2>
            <pre class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-[10px] font-mono text-slate-600 overflow-x-auto">{{ json_encode($document->metadata, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif
    </div>
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Actions rapides</h2>
            <div class="space-y-2">
                <a href="{{ route('documents.versions', $document) }}" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-600 text-xs font-bold transition-all"><i class="fa-solid fa-code-branch text-blue-400 text-[10px]"></i>Versions</a>
                <a href="{{ route('documents.audit', $document) }}" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-purple-50 hover:text-purple-700 text-slate-600 text-xs font-bold transition-all"><i class="fa-solid fa-clock-rotate-left text-purple-400 text-[10px]"></i>Journal d'audit</a>
                <a href="{{ route('documents.download', $document) }}" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-green-50 hover:text-green-700 text-slate-600 text-xs font-bold transition-all"><i class="fa-solid fa-download text-green-400 text-[10px]"></i>Telecharger</a>
                @if(!$document->isArchived())
                <form action="{{ route('documents.archive', $document) }}" method="POST">@csrf
                <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-amber-50 hover:text-amber-700 text-slate-600 text-xs font-bold transition-all"><i class="fa-solid fa-box-archive text-amber-400 text-[10px]"></i>Archiver</button></form>
                @endif
                @if(auth()->user()->hasRole('admin'))
                <form action="{{ route('documents.destroy', $document) }}" method="POST" onsubmit="return confirm('Supprimer ?');">@csrf @method('DELETE')
                <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-red-50 hover:text-red-700 text-slate-600 text-xs font-bold transition-all"><i class="fa-solid fa-trash-can text-red-400 text-[10px]"></i>Supprimer</button></form>
                @endif
            </div>
        </div>
        <a href="{{ route('documents.index') }}" class="flex items-center justify-center gap-2 w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 text-xs font-bold px-4 py-3 rounded-xl transition-all"><i class="fa-solid fa-arrow-left text-[10px]"></i>Retour</a>
    </div>
</div>

{{-- TAB: COMMENTAIRES --}}
<div x-show="tab === 'comments'" x-cloak class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Ajouter un commentaire</h2>
        </div>
        <form action="{{ route('documents.comments.store', $document) }}" method="POST" class="p-5 flex gap-3">
            @csrf
            <div class="w-8 h-8 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-xs shrink-0 mt-0.5">{{ strtoupper(substr(auth()->user()->full_name, 0, 1)) }}</div>
            <div class="flex-1">
                <textarea name="content" rows="3" required placeholder="Votre commentaire..." class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all resize-none"></textarea>
                <div class="flex justify-end mt-2">
                    <button type="submit" class="inline-flex items-center gap-1.5 bg-orange-600 hover:bg-orange-500 text-white text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-lg transition-all"><i class="fa-solid fa-paper-plane text-[8px]"></i>Envoyer</button>
                </div>
            </div>
        </form>
    </div>
    @php $comments = $document->comments()->with('user','replies.user')->get(); @endphp
    @forelse($comments as $comment)
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl {{ $comment->is_internal ? 'bg-amber-100' : 'bg-slate-100' }} flex items-center justify-center text-slate-600 font-black text-xs shrink-0">{{ strtoupper(substr($comment->user->full_name, 0, 1)) }}</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-slate-800">{{ $comment->user->full_name }}</span>
                    @if($comment->is_internal)<span class="text-[8px] font-black uppercase bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded">Interne</span>@endif
                    <span class="text-[9px] text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                </div>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ $comment->content }}</p>
                @foreach($comment->replies as $reply)
                <div class="flex items-start gap-2 mt-3 ml-4 pl-3 border-l-2 border-slate-100">
                    <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[9px] font-black text-slate-500 shrink-0">{{ strtoupper(substr($reply->user->full_name, 0, 1)) }}</div>
                    <div><p class="text-[10px] font-bold text-slate-700">{{ $reply->user->full_name }}</p><p class="text-[11px] text-slate-600 mt-0.5">{{ $reply->content }}</p></div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-12 text-center">
        <i class="fa-solid fa-comments text-slate-200 text-3xl mb-3"></i>
        <p class="text-xs font-bold text-slate-400">Aucun commentaire</p>
    </div>
    @endforelse
</div>

{{-- TAB: APPROBATION --}}
<div x-show="tab === 'approval'" x-cloak>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Workflow d'approbation</h2>
            <a href="{{ route('documents.approval', $document) }}" class="inline-flex items-center gap-1.5 bg-orange-600 hover:bg-orange-500 text-white text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all"><i class="fa-solid fa-arrow-right text-[8px]"></i>Gerer</a>
        </div>
        @forelse($document->approvalSteps as $step)
        <div class="flex items-center gap-4 px-5 py-4 border-b border-slate-50 last:border-0">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs {{ $step->status === 'approved' ? 'bg-green-100 text-green-600' : ($step->status === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-500') }}">{{ $step->step_order }}</div>
            <div class="flex-1"><p class="text-sm font-bold text-slate-800">{{ $step->approver->full_name }}</p><p class="text-[10px] text-slate-400">{{ $step->approver->email }}</p>@if($step->comment)<p class="text-xs text-slate-600 mt-1 italic">{{ $step->comment }}</p>@endif</div>
            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase {{ $step->status === 'approved' ? 'bg-green-50 text-green-600' : ($step->status === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600') }}">{{ $step->status }}</span>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-12 text-center"><i class="fa-solid fa-list-check text-slate-200 text-3xl mb-3"></i><p class="text-xs font-bold text-slate-400">Aucun workflow configure</p><a href="{{ route('documents.approval', $document) }}" class="mt-3 text-[10px] font-black text-orange-600 hover:underline uppercase">Configurer</a></div>
        @endforelse
    </div>
</div>

{{-- TAB: SIGNATURES --}}
<div x-show="tab === 'signatures'" x-cloak>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Signatures numeriques</h2>
            <a href="{{ route('documents.signatures', $document) }}" class="inline-flex items-center gap-1.5 bg-orange-600 hover:bg-orange-500 text-white text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all"><i class="fa-solid fa-signature text-[8px]"></i>Signer</a>
        </div>
        @forelse($document->signatures as $sig)
        <div class="flex items-center gap-4 px-5 py-4 border-b border-slate-50 last:border-0">
            <div class="w-8 h-8 rounded-xl bg-purple-100 flex items-center justify-center shrink-0"><i class="fa-solid fa-signature text-purple-500 text-sm"></i></div>
            <div class="flex-1"><p class="text-sm font-bold text-slate-800">{{ $sig->user->full_name }}</p><p class="text-[10px] text-slate-400">{{ $sig->signed_at->format('d/m/Y H:i') }}@if($sig->reason)  {{ $sig->reason }}@endif</p></div>
            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase bg-green-50 text-green-600">Signe</span>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-12 text-center"><i class="fa-solid fa-signature text-slate-200 text-3xl mb-3"></i><p class="text-xs font-bold text-slate-400">Aucune signature</p><a href="{{ route('documents.signatures', $document) }}" class="mt-3 text-[10px] font-black text-orange-600 hover:underline uppercase">Signer ce document</a></div>
        @endforelse
    </div>
</div>

{{-- TAB: PARTAGES --}}
<div x-show="tab === 'shares'" x-cloak>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Partages actifs</h2>
            <a href="{{ route('documents.shares', $document) }}" class="inline-flex items-center gap-1.5 bg-orange-600 hover:bg-orange-500 text-white text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all"><i class="fa-solid fa-share-nodes text-[8px]"></i>Partager</a>
        </div>
        @forelse($document->shares()->where('is_active', true)->with('sharedBy','sharedWith')->get() as $share)
        <div class="flex items-center gap-4 px-5 py-4 border-b border-slate-50 last:border-0">
            <div class="w-8 h-8 rounded-xl bg-green-100 flex items-center justify-center shrink-0"><i class="fa-solid fa-share-nodes text-green-500 text-sm"></i></div>
            <div class="flex-1"><p class="text-sm font-bold text-slate-800">{{ $share->sharedWith?->full_name ?? 'Lien public' }}</p><p class="text-[10px] text-slate-400">Par {{ $share->sharedBy->full_name }}  {{ $share->created_at->diffForHumans() }}@if($share->expires_at)  Expire {{ $share->expires_at->format('d/m/Y') }}@endif</p></div>
            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase {{ $share->access_level === 'edit' ? 'bg-orange-50 text-orange-600' : 'bg-slate-100 text-slate-500' }}">{{ $share->access_level }}</span>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-12 text-center"><i class="fa-solid fa-share-nodes text-slate-200 text-3xl mb-3"></i><p class="text-xs font-bold text-slate-400">Aucun partage actif</p><a href="{{ route('documents.shares', $document) }}" class="mt-3 text-[10px] font-black text-orange-600 hover:underline uppercase">Partager ce document</a></div>
        @endforelse
    </div>
</div>

{{-- TAB: VERSIONS --}}
<div x-show="tab === 'versions'" x-cloak>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Historique des versions</h2>
            <a href="{{ route('documents.versions', $document) }}" class="text-[10px] font-bold text-slate-400 hover:text-orange-600">Voir tout</a>
        </div>
        @forelse($document->versions()->with('creator')->limit(10)->get() as $version)
        @php $isCurrent = $version->version_number == $document->version; @endphp
        <div class="flex items-center gap-4 px-5 py-4 border-b border-slate-50 last:border-0 {{ $isCurrent ? 'bg-orange-50/40' : '' }}">
            <div class="w-9 h-9 rounded-xl {{ $isCurrent ? 'bg-orange-100' : 'bg-slate-100' }} flex items-center justify-center shrink-0"><span class="font-mono font-black text-xs {{ $isCurrent ? 'text-orange-600' : 'text-slate-500' }}">v{{ $version->version_number }}</span></div>
            <div class="flex-1 min-w-0"><p class="text-xs font-bold text-slate-800">{{ $version->creator?->full_name ?? 'Systeme' }}</p><p class="text-[9px] text-slate-400">{{ $version->created_at->format('d/m/Y H:i') }}@if($version->change_description)  {{ $version->change_description }}@endif</p></div>
            @if($isCurrent)<span class="px-2 py-0.5 bg-orange-100 text-orange-600 rounded text-[8px] font-black uppercase shrink-0">Actuelle</span>
            @else<form action="{{ route('documents.versions.restore', [$document, $version->version_number]) }}" method="POST" class="inline">@csrf<button type="submit" class="text-[9px] font-black text-blue-600 hover:underline uppercase shrink-0">Restaurer</button></form>@endif
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-12 text-center"><i class="fa-solid fa-code-branch text-slate-200 text-3xl mb-3"></i><p class="text-xs font-bold text-slate-400">Aucune version</p></div>
        @endforelse
    </div>
</div>

{{-- TAB: HISTORIQUE --}}
<div x-show="tab === 'audit'" x-cloak>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Journal d'audit</h2>
            <a href="{{ route('documents.audit', $document) }}" class="text-[10px] font-bold text-slate-400 hover:text-orange-600">Voir tout</a>
        </div>
        @forelse($document->auditLogs()->with('user')->limit(15)->get() as $log)
        <div class="flex items-start gap-3 px-5 py-3.5 border-b border-slate-50 last:border-0 hover:bg-slate-50/50 transition-colors">
            <div class="w-7 h-7 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 mt-0.5"><i class="fa-solid fa-bolt text-orange-500 text-[9px]"></i></div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-800"><span class="text-orange-600">{{ $log->action }}</span>  {{ $log->user?->full_name ?? 'Systeme' }}</p>
                @if($log->description)<p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ $log->description }}</p>@endif
            </div>
            <span class="text-[9px] text-slate-400 font-mono shrink-0">{{ $log->created_at->diffForHumans() }}</span>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-12 text-center"><i class="fa-solid fa-clock-rotate-left text-slate-200 text-3xl mb-3"></i><p class="text-xs font-bold text-slate-400">Aucune activite</p></div>
        @endforelse
    </div>
</div>

</div>{{-- fin x-data --}}

<script>
function wordUpload() {
    return {
        dragging: false, fileName: '',
        handleFile(e) { const f = e.target.files[0]; if (f) this.fileName = f.name; },
        handleDrop(e) {
            this.dragging = false;
            const f = e.dataTransfer.files[0];
            if (f && (f.name.endsWith('.docx') || f.name.endsWith('.doc'))) {
                this.fileName = f.name;
                const dt = new DataTransfer(); dt.items.add(f);
                this.$refs.fileInput.files = dt.files;
            }
        }
    }
}
</script>
@endsection
