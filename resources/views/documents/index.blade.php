@extends('layouts.app')

@section('content')
@php
    $canMove = auth()->user()->hasAnyRole(['admin', 'editor']);
    // Dossier ouvert : cible par défaut de « Nouveau » et « Importer » (si l'utilisateur peut y ranger)
    $openFolderId = is_int($filters->get('category')) && auth()->user()->canFileInCategory($filters->get('category')) ? $filters->get('category') : null;
    $fileableFolders = $categories->filter(fn ($c) => auth()->user()->canFileInCategory($c->id));
    // Dossier ouvert (navigation) : il devient le titre de la page
    $currentFolder = is_int($filters->get('category')) ? $tree->get($filters->get('category')) : null;
    $folderPath = $currentFolder ? $tree->path($currentFolder->id) : [];
    $folderErrors = $errors->getBag('folder');
    // Navigation pure dans un dossier (aucun critère de recherche) : état « dossier vide » si rien à afficher
    $browsingFolder = $currentFolder && !array_diff_key($filters->filterParams(), array_flip(['category', 'subcats', 'sort']));
    $listCols = 'md:grid-cols-[1.25rem_6.5rem_minmax(0,1fr)_9rem_6.5rem_10rem]';
@endphp
<div x-data="documentIndex()">

    @include('components.document-preview-modal')

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col sm:flex-row {{ $currentFolder ? 'sm:items-end' : 'sm:items-center' }} sm:justify-between gap-4 mb-6">
        @if($currentFolder)
        @include('documents._folder-header')
        @else
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Documents</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">{{ brandName() }} — Archive documentaire</p>
        </div>
        @endif
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <a href="{{ route('documents.advanced-search') }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-sm">
                <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                <span class="hidden sm:inline">Recherche avancée</span>
            </a>
            <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 gap-0.5">
                <button @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-white shadow text-orange-600' : 'text-slate-400 hover:text-slate-600'"
                    class="px-3 py-1.5 rounded-lg transition-all text-xs">
                    <i class="fa-solid fa-list-ul"></i>
                </button>
                <button @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-white shadow text-orange-600' : 'text-slate-400 hover:text-slate-600'"
                    class="px-3 py-1.5 rounded-lg transition-all text-xs">
                    <i class="fa-solid fa-grip"></i>
                </button>
            </div>
            @if(auth()->user()->hasAnyRole(['admin', 'editor']))
            {{-- Dépôt de fichiers (tout format : Word, Excel, PowerPoint, PDF, images…) --}}
            <button @click="uploadModal = true"
                class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
                <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i> Ajouter des documents
            </button>
            @endif
        </div>
    </div>

    <div class="lg:flex lg:items-start lg:gap-6" x-data="{ foldersOpen: false }">

    {{-- ===== DOSSIERS ===== --}}
    <aside class="lg:w-64 lg:shrink-0 lg:sticky lg:top-4 mb-4 lg:mb-0">
        <button type="button" @click="foldersOpen = !foldersOpen"
                class="lg:hidden w-full flex items-center justify-between bg-white border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-600 shadow-sm">
            <span><i class="fa-solid fa-folder-tree text-amber-400 mr-2"></i>Dossiers
                @if($tree->get(is_int($filters->get('category')) ? $filters->get('category') : null))
                <span class="text-orange-600">· {{ $tree->get($filters->get('category'))->name }}</span>
                @endif
            </span>
            <i class="fa-solid fa-chevron-down text-[9px] transition-transform" :class="foldersOpen && 'rotate-180'"></i>
        </button>
        <div class="mt-2 lg:mt-0" :class="foldersOpen ? 'block' : 'hidden lg:block'">
            @include('documents._folders')
        </div>
    </aside>

    <div class="flex-1 min-w-0">

    {{-- ===== FILTRES ===== --}}
    @include('documents._subfolders')
    @include('documents._filters')

    {{-- ===== VUE LISTE ===== --}}
    <div x-show="viewMode === 'list'">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            {{-- Barre bulk (visible si sélection) --}}
            <div x-show="selected.length > 0"
                 x-transition
                 class="flex items-center gap-3 px-5 py-3 bg-orange-50 border-b border-orange-100">
                <span class="text-xs font-black text-orange-700">
                    <span x-text="selected.length"></span> sélectionné(s)
                </span>
                <div class="flex items-center gap-2 ml-2 flex-wrap">
                    <form :action="'{{ route('documents.bulk') }}'" method="POST" @submit.prevent="submitBulk('archive')">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 bg-amber-100 hover:bg-amber-200 text-amber-700 text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                            <i class="fa-solid fa-box-archive text-[9px]"></i> Archiver
                        </button>
                    </form>
                    @if(auth()->user()->hasRole('admin'))
                    <div x-data="{ catOpen: false }" class="relative">
                        <button @click="catOpen = !catOpen" class="inline-flex items-center gap-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                            <i class="fa-solid fa-folder text-[9px]"></i> Déplacer
                        </button>
                        <div x-show="catOpen" @click.outside="catOpen=false" class="absolute top-full mt-1 left-0 bg-white border border-slate-100 rounded-xl shadow-xl z-50 min-w-[200px] max-h-72 overflow-y-auto py-1">
                            @foreach($categories as $cat)
                            <button @click="submitBulk('move_category', {{ $cat->id }}); catOpen=false"
                                class="w-full text-left px-4 py-2 text-xs font-medium text-slate-700 hover:bg-orange-50 hover:text-orange-600 transition-colors"
                                style="padding-left: {{ 1 + $cat->depth * 0.75 }}rem">
                                <i class="fa-solid fa-folder text-amber-400 text-[10px] mr-1"></i>{{ $cat->name }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    <button @click="submitBulk('delete')" class="inline-flex items-center gap-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                        <i class="fa-solid fa-trash text-[9px]"></i> Supprimer
                    </button>
                    @endif
                </div>
                <button @click="selected = []" class="ml-auto text-[9px] text-slate-400 hover:text-slate-600 font-bold">
                    Désélectionner tout
                </button>
            </div>

            {{-- Header table --}}
            @if($documents->isNotEmpty())
            <div class="hidden md:grid {{ $listCols }} gap-4 px-6 py-3 bg-slate-50 border-b border-slate-100">
                <div class="flex items-center">
                    <input type="checkbox" @change="toggleAll($event)"
                           :checked="selected.length === {{ $documents->count() }} && {{ $documents->count() }} > 0"
                           class="w-4 h-4 text-orange-600 rounded border-slate-300 focus:ring-orange-500 cursor-pointer">
                </div>
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence</div>
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Titre</div>
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $currentFolder ? 'Emplacement' : 'Dossier' }}</div>
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Statut</div>
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</div>
            </div>
            @endif

            <div class="divide-y divide-slate-50">
                @forelse($documents as $doc)
                <div class="group px-4 md:px-6 py-4 hover:bg-slate-50/60 transition-colors"
                     @if($canMove) draggable="true" @dragstart="dragStart($event, {{ $doc->id }})" @dragend="dragEnd()" @endif
                     :class="dragIds.includes({{ $doc->id }}) && 'opacity-50'">

                    {{-- Desktop --}}
                    <div class="hidden md:grid {{ $listCols }} gap-4 items-center">
                        <div>
                            <input type="checkbox" :value="{{ $doc->id }}" x-model="selected"
                                   class="w-4 h-4 text-orange-600 rounded border-slate-300 focus:ring-orange-500 cursor-pointer">
                        </div>
                        <div class="min-w-0">
                            <span class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-lg whitespace-nowrap">
                                {{ $doc->reference }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg {{ strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION)) === 'pdf' ? 'bg-red-50' : 'bg-orange-50' }} flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                                <x-file-icon :document="$doc" class="text-xs group-hover:text-white transition-colors" />
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('documents.show', $doc) }}"
                                   class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate transition-colors block">
                                    @if($searchTerms)<x-highlight :segments="\App\Services\DocumentSearch::highlight($doc->title, $searchTerms)" />@else{{ $doc->title }}@endif
                                </a>
                                @if($searchTerms && ($snippet = \App\Services\DocumentSearch::snippet($doc->search_excerpt_source, $searchTerms, 160)))
                                <p class="text-[11px] text-slate-500 leading-snug mt-0.5 line-clamp-2"><x-highlight :segments="$snippet" /></p>
                                @endif
                            </div>
                        </div>
                        <div class="min-w-0">
                            @if($currentFolder && $doc->category_id === $currentFolder->id)
                            <span class="text-[11px] text-slate-300">Ce dossier</span>
                            @elseif($doc->category)
                            <a href="{{ $filters->url(['category' => $doc->category_id, 'subcats' => null, 'page' => null]) }}"
                               class="inline-flex items-center gap-1.5 max-w-full text-[11px] font-semibold text-slate-500 hover:text-orange-600 transition-colors"
                               title="{{ $tree->label($doc->category_id) }}">
                                <i class="fa-solid fa-folder text-amber-400 text-[10px] shrink-0"></i>
                                <span class="truncate">{{ $doc->category->name }}</span>
                            </a>
                            @else
                            <span class="text-[11px] text-slate-300">Sans dossier</span>
                            @endif
                        </div>
                        <div>
                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider whitespace-nowrap
                                {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                                   ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' :
                                   ($doc->status === 'archived' ? 'bg-slate-100 text-slate-400' :
                                                                  'bg-amber-50 text-amber-600')) }}">
                                {{ statusLabel($doc->status) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-end gap-0.5">
                            <a href="{{ route('documents.show', $doc) }}"
                               title="Voir"
                               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>
                            <button @click="previewDocument({{ $doc->id }})"
                               title="Aperçu rapide"
                               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-purple-50 hover:text-purple-600 transition-all">
                                <i class="fa-solid fa-expand text-xs"></i>
                            </button>
                            <a href="{{ route('documents.download', $doc) }}"
                               title="Télécharger"
                               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-green-50 hover:text-green-600 transition-all">
                                <i class="fa-solid fa-download text-xs"></i>
                            </a>
                            <a href="{{ route('documents.edit', $doc) }}"
                               title="Modifier"
                               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-orange-50 hover:text-orange-600 transition-all">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                            @if(auth()->user()->hasRole('admin'))
                            <form action="{{ route('documents.destroy', $doc) }}" method="POST"
                                  onsubmit="return confirm('Supprimer ce document ?');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                    {{-- Mobile --}}
                    <div class="md:hidden flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl {{ strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION)) === 'pdf' ? 'bg-red-50' : 'bg-orange-50' }} flex items-center justify-center shrink-0">
                            <x-file-icon :document="$doc" class="text-sm" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('documents.show', $doc) }}"
                               class="text-sm font-bold text-slate-800 hover:text-orange-600 block truncate">
                                {{ $doc->title }}
                            </a>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="font-mono text-[9px] text-slate-400">{{ $doc->reference }}</span>
                                <span class="text-slate-200">•</span>
                                @if(!$currentFolder || $doc->category_id !== $currentFolder->id)
                                <span class="text-[9px] font-bold text-slate-400">{{ $doc->category?->name ?? 'Sans dossier' }}</span>
                                @endif
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase
                                    {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                                       ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600') }}">
                                    {{ statusLabel($doc->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <a href="{{ route('documents.download', $doc) }}"
                               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-green-50 hover:text-green-600 transition-all">
                                <i class="fa-solid fa-download text-xs"></i>
                            </a>
                            <a href="{{ route('documents.edit', $doc) }}"
                               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-orange-50 hover:text-orange-600 transition-all">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>
                        </div>
                    </div>

                </div>
                @empty
                @if($browsingFolder)
                @include('documents._empty-folder')
                @else
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-folder-open text-slate-300 text-2xl"></i>
                    </div>
                    @if($filters->isFiltered())
                    <p class="text-sm font-bold text-slate-500">Aucun document ne correspond à ces critères</p>
                    <p class="text-xs text-slate-400 mt-1">Retirez un filtre ou essayez d'autres mots.</p>
                    <a href="{{ route('documents.index', $currentFolder ? ['category' => $currentFolder->id] : []) }}"
                       class="mt-4 inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-5 py-2.5 rounded-xl transition-all">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i> Effacer les filtres
                    </a>
                    @else
                    <p class="text-sm font-bold text-slate-400">Aucun document pour le moment</p>
                    @if(auth()->user()->hasAnyRole(['admin', 'editor']))
                    <p class="text-xs text-slate-300 mt-1">Déposez vos fichiers : Word, Excel, PowerPoint, PDF, images…</p>
                    <div class="mt-4 flex gap-2">
                        <button @click="uploadModal = true"
                            class="inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
                            <i class="fa-solid fa-cloud-arrow-up text-[10px]"></i> Ajouter des documents
                        </button>
                    </div>
                    @endif
                    @endif
                </div>
                @endif
                @endforelse
            </div>

            {{-- Pagination --}}
            @if($documents->hasPages())
            <div class="px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    {{ $documents->firstItem() }}–{{ $documents->lastItem() }} sur {{ $documents->total() }} documents
                </p>
                {{ $documents->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ===== VUE GRILLE ===== --}}
    <div x-show="viewMode === 'grid'" x-cloak>
        @if($documents->isEmpty() && $browsingFolder)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">@include('documents._empty-folder')</div>
        @elseif($documents->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-2xl border border-slate-100">
            <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-folder-open text-slate-300 text-2xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">{{ $filters->isFiltered() ? 'Aucun document ne correspond à ces critères' : 'Aucun document' }}</p>
            @if($filters->isFiltered())
            <a href="{{ route('documents.index', $currentFolder ? ['category' => $currentFolder->id] : []) }}" class="mt-3 text-xs font-bold text-orange-600 hover:underline">Effacer les filtres</a>
            @endif
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4">
            @foreach($documents as $doc)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-orange-200 transition-all group overflow-hidden"
                 @if($canMove) draggable="true" @dragstart="dragStart($event, {{ $doc->id }})" @dragend="dragEnd()" @endif
                 :class="dragIds.includes({{ $doc->id }}) && 'opacity-50'">
                <div class="aspect-square bg-slate-50 flex items-center justify-center group-hover:bg-orange-50 transition-colors relative">
                    <x-file-icon :document="$doc" class="text-4xl opacity-40 group-hover:opacity-100 transition-opacity" />
                    @if($doc->is_confidential)
                    <span class="absolute top-2 right-2 w-5 h-5 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-lock text-red-500 text-[8px]"></i>
                    </span>
                    @endif
                </div>
                <div class="p-3">
                    <a href="{{ route('documents.show', $doc) }}"
                       class="text-[11px] font-black text-slate-800 hover:text-orange-600 block truncate leading-tight transition-colors">
                        {{ $doc->title }}
                    </a>
                    <p class="text-[9px] text-slate-400 font-mono mt-0.5 truncate">{{ $doc->reference }}</p>
                    <div class="flex items-center justify-between mt-3 pt-2 border-t border-slate-50">
                        <button @click="previewDocument({{ $doc->id }})"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:bg-blue-50 hover:text-blue-500 transition-all">
                            <i class="fa-solid fa-eye text-[10px]"></i>
                        </button>
                        <a href="{{ route('documents.download', $doc) }}"
                           class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:bg-green-50 hover:text-green-500 transition-all">
                            <i class="fa-solid fa-download text-[10px]"></i>
                        </a>
                        <a href="{{ route('documents.edit', $doc) }}"
                           class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:bg-orange-50 hover:text-orange-500 transition-all">
                            <i class="fa-solid fa-pen text-[10px]"></i>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @if($documents->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $documents->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>

    </div>{{-- colonne principale --}}
    </div>{{-- dossiers + liste --}}

    {{-- ===== IMPORT MULTIPLE ===== --}}
    @if(auth()->user()->hasAnyRole(['admin', 'editor']))
    @include('documents._bulk-import')
    @endif

    {{-- BULK FORM caché --}}
    <form id="bulk-form" action="{{ route('documents.bulk') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="action" id="bulk-action">
        <input type="hidden" name="category_id" id="bulk-category">
        <div id="bulk-ids"></div>
    </form>

</div>

<script>
function documentIndex() {
    return {
        // Fenêtre dossier : 'create' | 'rename' | null (rouverte si le formulaire a été refusé)
        folderModal: @js($folderErrors->any() ? (old('_folder_mode') === 'rename' ? 'rename' : 'create') : null),
        uploadModal: false,
        dragIds:     [],
        dropTarget:  null,
        viewMode:    localStorage.getItem('docViewMode') || 'list',
        selected:    [],

        init() {
            this.$watch('viewMode', v => localStorage.setItem('docViewMode', v));
            // Lien « Importer des documents » (palette Ctrl+K) : #import ouvre la fenêtre d'import
            const openImport = () => {
                if (location.hash === '#import' && @js(auth()->user()->hasAnyRole(['admin', 'editor']))) {
                    this.uploadModal = true;
                    history.replaceState(null, '', location.pathname + location.search);
                }
            };
            openImport();
            window.addEventListener('hashchange', openImport);
        },

        toggleAll(e) {
            if (e.target.checked) {
                this.selected = [{{ $documents->pluck('id')->join(',') }}];
            } else {
                this.selected = [];
            }
        },

        submitBulk(action, categoryId = null) {
            if (!this.selected.length) return;
            if (action === 'delete' && !confirm('Supprimer ' + this.selected.length + ' document(s) ?')) return;

            document.getElementById('bulk-action').value   = action;
            document.getElementById('bulk-category').value = categoryId || '';

            const container = document.getElementById('bulk-ids');
            container.innerHTML = '';
            this.selected.forEach(id => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'document_ids[]';
                inp.value = id;
                container.appendChild(inp);
            });

            document.getElementById('bulk-form').submit();
        },

        // Glisser un document (ou la sélection qui le contient) vers un dossier
        dragStart(event, id) {
            this.dragIds = this.selected.includes(id) ? [...this.selected] : [id];
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', 'ged-documents:' + this.dragIds.join(','));
        },
        dragEnd() {
            setTimeout(() => { this.dragIds = []; this.dropTarget = null; }, 50);
        },
        dropOn(categoryId, name) {
            const ids = this.dragIds;
            this.dropTarget = null;
            if (!ids.length) return;
            if (!confirm('Ranger ' + ids.length + ' document(s) dans « ' + name + ' » ?')) { this.dragIds = []; return; }
            this.selected = ids;
            this.submitBulk('move_category', categoryId);
        },

        async previewDocument(documentId) {
            try {
                const response = await fetch(`/api/documents/${documentId}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const docData = await response.json();
                // Dispatch un event Alpine global pour ouvrir la modal
                window.dispatchEvent(new CustomEvent('open-preview', { detail: docData }));
            } catch (error) { console.error('Erreur preview:', error); }
        }
    }
}
</script>
@endsection
