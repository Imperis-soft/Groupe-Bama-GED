@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- ===== BREADCRUMB + ACTIONS ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
            <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors">Documents</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <span class="text-slate-600 font-bold truncate max-w-[200px]">{{ $document->title }}</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('documents.edit-online', $document) }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-4 py-2.5 rounded-xl shadow-lg shadow-blue-200 transition-all">
                <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                <span class="hidden sm:inline">Éditer en ligne</span>
            </a>
            <a href="ms-word:ofe|u|{{ url('/webdav/' . $document->id) }}"
               class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-4 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
                <i class="fa-brands fa-microsoft text-[10px]"></i>
                <span class="hidden sm:inline">Ouvrir dans Word</span>
            </a>
            <a href="{{ route('documents.download', $document) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-download text-[10px]"></i>
                <span class="hidden sm:inline">Télécharger</span>
            </a>
            <a href="{{ route('documents.edit', $document) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-sliders text-[10px]"></i>
                <span class="hidden sm:inline">Modifier</span>
            </a>
        </div>
    </div>

    {{-- ===== HERO CARD ===== --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- Banner --}}
        <div class="h-2 w-full
            {{ $document->status === 'approved' ? 'bg-green-500' :
               ($document->status === 'review'   ? 'bg-blue-500' :
               ($document->status === 'archived' ? 'bg-slate-400' : 'bg-amber-400')) }}">
        </div>

        <div class="p-5 md:p-7">
            <div class="flex flex-col md:flex-row md:items-start gap-5">

                {{-- Icône doc --}}
                <div class="w-16 h-16 rounded-2xl bg-orange-50 flex items-center justify-center shrink-0 border border-orange-100">
                    <i class="fa-solid fa-file-word text-orange-500 text-3xl"></i>
                </div>

                {{-- Infos principales --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg">
                            {{ $document->reference }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider
                            {{ $document->status === 'approved' ? 'bg-green-50 text-green-600 border border-green-100' :
                               ($document->status === 'review'   ? 'bg-blue-50 text-blue-600 border border-blue-100' :
                               ($document->status === 'archived' ? 'bg-slate-100 text-slate-500 border border-slate-200' :
                                                                   'bg-amber-50 text-amber-600 border border-amber-100')) }}">
                            {{ $document->status }}
                        </span>
                        @if($document->is_confidential)
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider bg-red-50 text-red-600 border border-red-100">
                            <i class="fa-solid fa-lock mr-1"></i> Confidentiel
                        </span>
                        @endif
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold bg-slate-50 text-slate-500 border border-slate-100">
                            v{{ $document->version }}
                        </span>
                    </div>

                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight leading-tight">
                        {{ $document->title }}
                    </h1>

                    <p class="text-xs text-slate-400 mt-1.5">
                        Créé par <span class="font-bold text-slate-600">{{ $document->creator?->full_name ?? 'Inconnu' }}</span>
                        le <span class="font-bold text-slate-600">{{ $document->created_at->translatedFormat('d F Y à H:i') }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== GRILLE DÉTAILS ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Métadonnées (2/3) --}}
        <div class="md:col-span-2 space-y-4">

            {{-- Informations --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Informations</h2>
                <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Catégorie</p>
                        <p class="text-sm font-bold text-slate-800">{{ $document->category?->name ?? 'Général' }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Version</p>
                        <p class="text-sm font-bold text-slate-800">{{ $document->version }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Créé le</p>
                        <p class="text-sm font-bold text-slate-800">{{ $document->created_at->format('d/m/Y') }}</p>
                        <p class="text-[9px] text-slate-400">{{ $document->created_at->format('H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Modifié le</p>
                        <p class="text-sm font-bold text-slate-800">{{ $document->updated_at->format('d/m/Y') }}</p>
                        <p class="text-[9px] text-slate-400">{{ $document->updated_at->diffForHumans() }}</p>
                    </div>
                    @if($document->expires_at)
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Expiration</p>
                        <p class="text-sm font-bold {{ $document->expires_at->isPast() ? 'text-red-600' : 'text-slate-800' }}">
                            {{ $document->expires_at->format('d/m/Y') }}
                        </p>
                        @if($document->expires_at->isPast())
                            <p class="text-[9px] text-red-500 font-bold">Expiré</p>
                        @else
                            <p class="text-[9px] text-slate-400">{{ $document->expires_at->diffForHumans() }}</p>
                        @endif
                    </div>
                    @endif
                    @if($document->retention_years)
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Rétention</p>
                        <p class="text-sm font-bold text-slate-800">{{ $document->retention_years }} ans</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Tags --}}
            @if($document->tags && count($document->tags))
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Tags</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($document->tags as $tag)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-orange-50 hover:text-orange-600 text-slate-600 rounded-xl text-xs font-bold transition-colors cursor-default">
                        <i class="fa-solid fa-tag text-[8px]"></i>
                        {{ trim($tag) }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Métadonnées JSON --}}
            @if($document->metadata)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Métadonnées</h2>
                <pre class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-[10px] font-mono text-slate-600 overflow-x-auto leading-relaxed">{{ json_encode($document->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
            @endif

        </div>

        {{-- Sidebar droite (1/3) --}}
        <div class="space-y-4">

            {{-- Actions rapides --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('documents.versions', $document) }}"
                       class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-600 text-xs font-bold transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-code-branch text-blue-500 text-[10px]"></i>
                        </div>
                        Historique des versions
                        <i class="fa-solid fa-chevron-right text-[8px] ml-auto text-slate-300 group-hover:text-blue-400"></i>
                    </a>
                    <a href="{{ route('documents.audit', $document) }}"
                       class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-purple-50 hover:text-purple-700 text-slate-600 text-xs font-bold transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-shield-halved text-purple-500 text-[10px]"></i>
                        </div>
                        Journal d'audit
                        <i class="fa-solid fa-chevron-right text-[8px] ml-auto text-slate-300 group-hover:text-purple-400"></i>
                    </a>
                    <a href="{{ route('documents.download', $document) }}"
                       class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-green-50 hover:text-green-700 text-slate-600 text-xs font-bold transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-download text-green-500 text-[10px]"></i>
                        </div>
                        Télécharger le fichier
                        <i class="fa-solid fa-chevron-right text-[8px] ml-auto text-slate-300 group-hover:text-green-400"></i>
                    </a>
                    @if(!$document->isArchived())
                    <form action="{{ route('documents.archive', $document) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-amber-50 hover:text-amber-700 text-slate-600 text-xs font-bold transition-all group">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-box-archive text-amber-500 text-[10px]"></i>
                            </div>
                            Archiver ce document
                            <i class="fa-solid fa-chevron-right text-[8px] ml-auto text-slate-300 group-hover:text-amber-400"></i>
                        </button>
                    </form>
                    @endif
                    @if(auth()->user()->hasRole('admin'))
                    <form action="{{ route('documents.destroy', $document) }}" method="POST"
                          onsubmit="return confirm('Supprimer définitivement ce document ?');">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-red-50 hover:text-red-700 text-slate-600 text-xs font-bold transition-all group">
                            <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-trash-can text-red-500 text-[10px]"></i>
                            </div>
                            Supprimer
                            <i class="fa-solid fa-chevron-right text-[8px] ml-auto text-slate-300 group-hover:text-red-400"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Statut archivage --}}
            @if($document->isArchived())
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-box-archive text-slate-500 text-sm"></i>
                    <p class="text-xs font-black text-slate-600 uppercase tracking-wider">Archivé</p>
                </div>
                @if($document->archived_at)
                <p class="text-[10px] text-slate-400">Le {{ $document->archived_at->format('d/m/Y à H:i') }}</p>
                @endif
            </div>
            @endif

            {{-- Retour --}}
            <a href="{{ route('documents.index') }}"
               class="flex items-center justify-center gap-2 w-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-500 text-xs font-bold px-4 py-3 rounded-xl transition-all">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour à la liste
            </a>

        </div>
    </div>

</div>
@endsection
