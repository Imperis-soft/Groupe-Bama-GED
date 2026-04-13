@extends('layouts.app')

@section('content')
<div class="space-y-5" x-data="{ createOpen: false, editOpen: false, editData: {} }">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Catégories</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">
                {{ $categories->total() }} catégorie(s) racine · {{ \App\Models\Category::count() }} au total
            </p>
        </div>
        @if(auth()->user()->hasRole('admin'))
        <button @click="createOpen = true"
            class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all self-start sm:self-auto">
            <i class="fa-solid fa-folder-plus text-[10px]"></i> Nouvelle catégorie
        </button>
        @endif
    </div>

    {{-- GRILLE CATÉGORIES --}}
    @if($categories->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-16 text-center">
        <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
            <i class="fa-solid fa-folder-open text-slate-300 text-xl"></i>
        </div>
        <p class="text-sm font-bold text-slate-400">Aucune catégorie</p>
        @if(auth()->user()->hasRole('admin'))
        <button @click="createOpen = true"
            class="mt-4 inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
            <i class="fa-solid fa-plus text-[10px]"></i> Créer la première
        </button>
        @endif
    </div>
    @else

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($categories as $cat)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-orange-200 transition-all group overflow-hidden">

            {{-- Bande couleur --}}
            <div class="h-1 w-full bg-gradient-to-r from-orange-400 to-orange-600"></div>

            <div class="p-5">
                {{-- Icône + nom --}}
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                            <i class="fa-solid fa-folder-tree text-orange-500 text-sm group-hover:text-white transition-colors"></i>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('categories.show', $cat) }}"
                               class="text-sm font-black text-slate-900 hover:text-orange-600 transition-colors block truncate leading-tight">
                                {{ $cat->name }}
                            </a>
                            <p class="text-[9px] font-mono text-orange-500 font-bold mt-0.5 truncate">{{ $cat->slug }}</p>
                        </div>
                    </div>
                    @if(auth()->user()->hasRole('admin'))
                    <div class="flex items-center gap-1 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="editData = {{ $cat->toJson() }}; editOpen = true"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                            <i class="fa-solid fa-pen text-[10px]"></i>
                        </button>
                        <form action="{{ route('categories.destroy', $cat) }}" method="POST"
                              onsubmit="return confirm('Supprimer {{ $cat->name }} ?');" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

                {{-- Description --}}
                @if($cat->description)
                <p class="text-[10px] text-slate-500 leading-relaxed mb-3 line-clamp-2">{{ $cat->description }}</p>
                @endif

                {{-- Stats --}}
                <div class="flex items-center justify-between pt-3 border-t border-slate-50">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-file-lines text-slate-300 text-[10px]"></i>
                            <span class="text-[10px] font-bold text-slate-500">{{ $cat->documents_count }} doc(s)</span>
                        </div>
                        @if($cat->children->count() > 0)
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-sitemap text-slate-300 text-[10px]"></i>
                            <span class="text-[10px] font-bold text-slate-500">{{ $cat->children->count() }} sous-cat.</span>
                        </div>
                        @endif
                    </div>
                    <a href="{{ route('categories.show', $cat) }}"
                       class="text-[9px] font-black text-orange-600 hover:underline uppercase tracking-wider">
                        Voir <i class="fa-solid fa-arrow-right text-[8px]"></i>
                    </a>
                </div>

                {{-- Sous-catégories --}}
                @if($cat->children->count() > 0)
                <div class="mt-3 pt-3 border-t border-slate-50">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($cat->children->take(4) as $child)
                        <a href="{{ route('categories.show', $child) }}"
                           class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 hover:bg-orange-50 hover:text-orange-600 text-slate-500 rounded-lg text-[9px] font-bold transition-colors">
                            <i class="fa-solid fa-folder text-[8px]"></i>
                            {{ $child->name }}
                        </a>
                        @endforeach
                        @if($cat->children->count() > 4)
                        <span class="px-2 py-1 bg-slate-50 text-slate-400 rounded-lg text-[9px] font-bold">
                            +{{ $cat->children->count() - 4 }}
                        </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- PAGINATION --}}
    @if($categories->hasPages())
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
            {{ $categories->firstItem() }}–{{ $categories->lastItem() }} sur {{ $categories->total() }} catégories
        </p>
        {{ $categories->links() }}
    </div>
    @endif

    @endif

    {{-- MODAL CRÉER --}}
    <div x-show="createOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div @click="createOpen = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div @click.stop
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-4"
             class="relative bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-orange-100 flex items-center justify-center">
                        <i class="fa-solid fa-folder-plus text-orange-500 text-sm"></i>
                    </div>
                    <h2 class="text-sm font-black text-slate-900">Nouvelle catégorie</h2>
                </div>
                <button @click="createOpen = false"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <form action="{{ route('categories.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Catégorie parente</label>
                    <select name="parent_id"
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        <option value="">Aucune (catégorie racine)</option>
                        @foreach($allCategories as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nom <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="create_name" required
                           oninput="generateSlug(this.value, 'create_slug')"
                           placeholder="Ex: Ressources Humaines"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" id="create_slug" required
                           placeholder="ressources-humaines"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-mono font-bold text-orange-500 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Description optionnelle..."
                        class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all resize-none"></textarea>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="createOpen = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                        Annuler
                    </button>
                    <button type="submit"
                        class="flex-1 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                        <i class="fa-solid fa-check mr-1.5"></i> Créer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ÉDITER --}}
    <div x-show="editOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div @click="editOpen = false"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div @click.stop
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 translate-y-4"
             class="relative bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center">
                        <i class="fa-solid fa-pen text-blue-500 text-sm"></i>
                    </div>
                    <h2 class="text-sm font-black text-slate-900">Modifier la catégorie</h2>
                </div>
                <button @click="editOpen = false"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 transition-colors">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <form :action="'/categories/' + editData.id" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Catégorie parente</label>
                    <select name="parent_id"
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        <option value="">Aucune (catégorie racine)</option>
                        @foreach($allCategories as $parent)
                        <option :value="'{{ $parent->id }}'"
                                :selected="editData.parent_id == '{{ $parent->id }}'">
                            {{ $parent->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nom <span class="text-red-500">*</span></label>
                    <input type="text" name="name" :value="editData.name" required
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Slug <span class="text-red-500">*</span></label>
                    <input type="text" name="slug" :value="editData.slug" required
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-mono font-bold text-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Description</label>
                    <textarea name="description" rows="2"
                        class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all resize-none"
                        x-text="editData.description"></textarea>
                </div>
                <div class="flex gap-3 pt-1">
                    <button type="button" @click="editOpen = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                        Annuler
                    </button>
                    <button type="submit"
                        class="flex-1 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function generateSlug(value, targetId) {
    document.getElementById(targetId).value = value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}
</script>
@endsection
