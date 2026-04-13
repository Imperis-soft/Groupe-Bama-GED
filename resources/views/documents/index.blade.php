@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="documentIndex()">
    {{-- Modal de prévisualisation --}}
    @include('components.document-preview-modal')
    
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Documents</h1>
            <p class="text-[10px] md:text-xs text-slate-500 font-medium">Groupe Bama — Architecture Imperis Sarl</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2 md:gap-3">
            <a href="{{ route('documents.advanced-search') }}" class="flex-1 md:flex-none bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 md:px-4 md:py-2.5 rounded-xl text-[10px] md:text-xs font-bold transition-all flex items-center justify-center gap-2 border border-slate-200">
                <i class="fa-solid fa-search"></i> <span class="hidden sm:inline">Recherche avancée</span>
            </a>

            <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white shadow-sm text-orange-600' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg transition-all">
                    <i class="fa-solid fa-list-ul text-xs"></i>
                </button>
                <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white shadow-sm text-orange-600' : 'text-slate-400'" class="px-3 py-1.5 rounded-lg transition-all">
                    <i class="fa-solid fa-grip text-xs"></i>
                </button>
            </div>

            <button @click="openModal = true" class="flex-1 md:flex-none bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 md:py-2.5 rounded-xl text-[10px] md:text-xs font-bold shadow-lg shadow-orange-100 transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus"></i> <span class="sm:inline">Nouveau</span>
            </button>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('documents.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="sm:col-span-2 relative">
            <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un document..." 
                   class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs focus:ring-2 focus:ring-orange-500 outline-none transition-all">
        </div>
        <select name="category" class="bg-white border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none">
            <option value="">Toutes les catégories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="sort" class="flex-1 bg-white border border-slate-200 rounded-xl px-4 py-2 text-xs font-bold text-slate-600 outline-none">
                <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Plus récents</option>
                <option value="version" {{ request('sort') == 'version' ? 'selected' : '' }}>Par Version</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-xl text-xs font-bold">Filtrer</button>
        </div>
    </form>

    {{-- Vue Liste --}}
    <div x-show="viewMode === 'list'" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Référence</th>
                        <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Titre</th>
                        <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest text-center">Catégorie</th>
                        <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($documents as $doc)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 font-mono font-bold text-slate-400">{{ $doc->reference }}</td>
                        <td class="px-6 py-4 font-bold text-slate-900">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-file-word text-orange-600"></i>
                                <a href="{{ route('documents.show', $doc) }}" class="hover:underline">{{ $doc->title }}</a>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 bg-slate-100 rounded text-[10px] font-bold">{{ $doc->category?->name ?? 'Général' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="ms-word:ofe|u|{{ url('/webdav/' . $doc->id) }}" class="text-orange-600 font-bold hover:underline mr-4">Éditer</a>
                            <a href="{{ route('documents.download', $doc) }}" class="text-green-600 font-bold hover:underline mr-4"><i class="fa-solid fa-download"></i> Télécharger</a>
                            <a href="{{ route('documents.edit', $doc) }}" class="text-slate-600 hover:underline mr-4">Modifier</a>
                            <form action="{{ route('documents.destroy', $doc) }}" method="POST" onsubmit="return confirm('Supprimer ce document ?');" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 font-bold hover:underline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Vue Grille --}}
    <div x-show="viewMode === 'grid'" x-cloak class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($documents as $doc)
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm hover:border-orange-200 transition-all group">
            <div class="aspect-square bg-slate-50 rounded-xl flex items-center justify-center mb-3 group-hover:bg-orange-50 transition-colors">
                <i class="fa-solid fa-file-word text-3xl text-slate-300 group-hover:text-orange-600"></i>
            </div>
            <p class="text-[11px] font-black text-slate-900 truncate"><a href="{{ route('documents.show', $doc) }}" class="hover:underline">{{ $doc->title }}</a></p>
            <p class="text-[9px] text-slate-400 uppercase font-bold mt-1">{{ $doc->reference }}</p>
            <div class="mt-3 pt-3 border-t border-slate-50 flex justify-between">
                <button @click="previewDocument({{ $doc->id }})" class="text-slate-400 hover:text-blue-500">
                    <i class="fa-solid fa-eye text-xs"></i>
                </button>
                <a href="{{ route('documents.download', $doc) }}" class="text-slate-400 hover:text-green-600" title="Télécharger">
                    <i class="fa-solid fa-download text-xs"></i>
                </a>
                <a href="ms-word:ofe|u|{{ url('/webdav/' . $doc->id) }}" class="text-slate-400 hover:text-orange-600" title="Éditer dans Word">
                    <i class="fa-solid fa-pen text-xs"></i>
                </a>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Modal de Création (Formulaire Complet) --}}
    <div x-show="openModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div @click="openModal = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        <div class="relative bg-white w-full max-w-2xl rounded-3xl shadow-2xl p-6 md:p-8 max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-black text-slate-900">Créer un nouveau document</h2>
                <button @click="openModal = false" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            
            <form action="{{ route('documents.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider">Informations générales</h3>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Titre *</label>
                        <input type="text" name="title" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-orange-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Catégorie</label>
                        <select name="category_id" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">Sélectionner une catégorie</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl">
                        <input type="checkbox" name="is_confidential" value="1" id="is_confidential" class="w-4 h-4 text-orange-600 bg-white border-slate-300 rounded focus:ring-orange-500">
                        <label for="is_confidential" class="text-sm font-bold text-slate-700 cursor-pointer">
                            <i class="fa-solid fa-lock mr-2 text-red-500"></i>
                            Document confidentiel
                        </label>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Tags (séparés par des virgules)</label>
                        <input type="text" name="tags" placeholder="ex: contrat, finance, urgent" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <input type="hidden" name="status" value="draft">
                <input type="hidden" name="retention_years" value="5">

                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="openModal = false" class="flex-1 bg-slate-100 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase hover:bg-slate-200 transition">Annuler</button>
                    <button type="submit" class="flex-1 bg-orange-600 text-white py-3 rounded-xl font-bold text-xs uppercase shadow-lg shadow-orange-100 hover:bg-orange-700 transition">Créer le document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function documentIndex() {
    return {
        openModal: {{ $errors->any() ? 'true' : 'false' }},
        viewMode: 'list',
        search: '',
        previewModal: false,
        selectedDocument: null,
        async previewDocument(documentId) {
            try {
                const response = await fetch(`/api/documents/${documentId}`);
                const docData = await response.json();
                this.selectedDocument = docData;
                this.previewModal = true;
                const modal = document.querySelector('[x-data="documentPreview()"]');
                if (modal && modal._x_dataStack) {
                    modal._x_dataStack[0].document = docData;
                    modal._x_dataStack[0].openModal();
                }
            } catch (error) { console.error('Erreur:', error); }
        }
    }
}
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection