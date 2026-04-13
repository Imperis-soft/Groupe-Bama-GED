@extends('layouts.app')

@section('content')
<div x-data="documentIndex()">

    @include('components.document-preview-modal')

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Documents</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Groupe Bama — Archive documentaire</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('documents.advanced-search') }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-sm">
                <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                <span class="hidden sm:inline">Recherche avancée</span>
            </a>

            {{-- Toggle vue --}}
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

            <button @click="openModal = true"
                class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
                <i class="fa-solid fa-plus text-[10px]"></i> Nouveau
            </button>
        </div>
    </div>

    {{-- ===== FILTRES ===== --}}
    <form method="GET" action="{{ route('documents.index') }}"
          class="bg-white border border-slate-100 rounded-2xl shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="sm:col-span-2 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Rechercher par titre, référence..."
                       class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-4 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
            </div>
            <select name="category"
                    class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                <option value="">Toutes les catégories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <select name="sort"
                        class="flex-1 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Plus récents</option>
                    <option value="version" {{ request('sort') == 'version' ? 'selected' : '' }}>Par version</option>
                </select>
                <button type="submit"
                    class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2.5 rounded-xl text-xs font-black transition-all active:scale-95">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>
        </div>
    </form>

    {{-- ===== VUE LISTE ===== --}}
    <div x-show="viewMode === 'list'">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            {{-- Header table --}}
            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-slate-50 border-b border-slate-100">
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence</div>
                <div class="col-span-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Titre</div>
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Catégorie</div>
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Statut</div>
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</div>
            </div>

            <div class="divide-y divide-slate-50">
                @forelse($documents as $doc)
                <div class="group px-4 md:px-6 py-4 hover:bg-slate-50/60 transition-colors">

                    {{-- Desktop --}}
                    <div class="hidden md:grid grid-cols-12 gap-4 items-center">
                        <div class="col-span-2">
                            <span class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-lg">
                                {{ $doc->reference }}
                            </span>
                        </div>
                        <div class="col-span-4 flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                                <i class="fa-solid fa-file-word text-orange-500 text-xs group-hover:text-white transition-colors"></i>
                            </div>
                            <a href="{{ route('documents.show', $doc) }}"
                               class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate transition-colors">
                                {{ $doc->title }}
                            </a>
                        </div>
                        <div class="col-span-2">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold uppercase tracking-wider">
                                {{ $doc->category?->name ?? 'Général' }}
                            </span>
                        </div>
                        <div class="col-span-2">
                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider
                                {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                                   ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' :
                                   ($doc->status === 'archived' ? 'bg-slate-100 text-slate-400' :
                                                                  'bg-amber-50 text-amber-600')) }}">
                                {{ $doc->status }}
                            </span>
                        </div>
                        <div class="col-span-2 flex items-center justify-end gap-1">
                            <a href="{{ route('documents.show', $doc) }}"
                               title="Voir"
                               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                                <i class="fa-solid fa-eye text-xs"></i>
                            </a>
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
                        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-word text-orange-500 text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('documents.show', $doc) }}"
                               class="text-sm font-bold text-slate-800 hover:text-orange-600 block truncate">
                                {{ $doc->title }}
                            </a>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="font-mono text-[9px] text-slate-400">{{ $doc->reference }}</span>
                                <span class="text-slate-200">•</span>
                                <span class="text-[9px] font-bold text-slate-400">{{ $doc->category?->name ?? 'Général' }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase
                                    {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                                       ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600') }}">
                                    {{ $doc->status }}
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
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-folder-open text-slate-300 text-2xl"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-400">Aucun document trouvé</p>
                    <p class="text-xs text-slate-300 mt-1">Créez votre premier document ou modifiez les filtres</p>
                    <button @click="openModal = true"
                        class="mt-4 inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
                        <i class="fa-solid fa-plus text-[10px]"></i> Créer un document
                    </button>
                </div>
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
        @if($documents->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center bg-white rounded-2xl border border-slate-100">
            <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-folder-open text-slate-300 text-2xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucun document</p>
        </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4">
            @foreach($documents as $doc)
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-orange-200 transition-all group overflow-hidden">
                <div class="aspect-square bg-slate-50 flex items-center justify-center group-hover:bg-orange-50 transition-colors relative">
                    <i class="fa-solid fa-file-word text-4xl text-slate-200 group-hover:text-orange-400 transition-colors"></i>
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

    {{-- ===== MODAL CRÉATION ===== --}}
    <div x-show="openModal" x-cloak
         class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div @click="openModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="relative bg-white w-full sm:max-w-xl rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[92vh] overflow-y-auto">

            {{-- Modal header --}}
            <div class="sticky top-0 bg-white border-b border-slate-100 px-6 py-4 flex items-center justify-between rounded-t-3xl z-10">
                <div>
                    <h2 class="text-base font-black text-slate-900">Nouveau document</h2>
                    <p class="text-[10px] text-slate-400 mt-0.5">Un QR code de vérification sera généré automatiquement</p>
                </div>
                <button @click="openModal = false"
                    class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form action="{{ route('documents.store') }}" method="POST" class="p-6 space-y-5">
                @csrf

                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Titre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" required value="{{ old('title') }}"
                           placeholder="Ex: Contrat de prestation N°2024-001"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                    @error('title')
                        <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Catégorie</label>
                        <select name="category_id"
                                class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                            <option value="">Sans catégorie</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Statut</label>
                        <select name="status"
                                class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                            <option value="draft">Brouillon</option>
                            <option value="review">En révision</option>
                            <option value="approved">Approuvé</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Tags</label>
                    <input type="text" name="tags" value="{{ old('tags') }}"
                           placeholder="contrat, finance, urgent..."
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>

                <div class="flex items-center gap-3 bg-red-50 border border-red-100 rounded-xl px-4 py-3">
                    <input type="checkbox" name="is_confidential" value="1" id="is_confidential"
                           class="w-4 h-4 text-red-500 border-red-300 rounded focus:ring-red-400"
                           {{ old('is_confidential') ? 'checked' : '' }}>
                    <label for="is_confidential" class="flex items-center gap-2 text-sm font-bold text-red-700 cursor-pointer select-none">
                        <i class="fa-solid fa-lock text-red-500"></i>
                        Document confidentiel
                    </label>
                </div>

                <input type="hidden" name="retention_years" value="5">

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="openModal = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                        Annuler
                    </button>
                    <button type="submit"
                        class="flex-1 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                        <i class="fa-solid fa-file-circle-plus mr-1.5"></i> Créer
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function documentIndex() {
    return {
        openModal: {{ $errors->any() ? 'true' : 'false' }},
        viewMode: localStorage.getItem('docViewMode') || 'list',
        init() {
            this.$watch('viewMode', v => localStorage.setItem('docViewMode', v));
        },
        async previewDocument(documentId) {
            try {
                const response = await fetch(`/api/documents/${documentId}`);
                const docData = await response.json();
                const modal = document.querySelector('[x-data="documentPreview()"]');
                if (modal && modal._x_dataStack) {
                    modal._x_dataStack[0].document = docData;
                    modal._x_dataStack[0].openModal();
                }
            } catch (error) { console.error('Erreur preview:', error); }
        }
    }
}
</script>
@endsection
