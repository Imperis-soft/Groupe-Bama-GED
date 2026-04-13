@extends('layouts.app')

@section('content')
<div class="space-y-5" x-data="{ createOpen: false, editOpen: false, editData: {} }">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Catégories</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">{{ $categories->count() }} catégorie(s) au total</p>
        </div>
        <button @click="createOpen = true"
            class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all self-start sm:self-auto">
            <i class="fa-solid fa-folder-plus text-[10px]"></i> Nouvelle catégorie
        </button>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        @if($categories->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-folder-open text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucune catégorie</p>
            <button @click="createOpen = true"
                class="mt-4 inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
                <i class="fa-solid fa-plus text-[10px]"></i> Créer la première
            </button>
        </div>
        @else

        {{-- Desktop --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Catégorie</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Documents</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($categories as $c)
                    <tr class="hover:bg-slate-50/60 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                                    <i class="fa-solid fa-folder-tree text-orange-500 text-sm group-hover:text-white transition-colors"></i>
                                </div>
                                <div>
                                    <a href="{{ route('categories.show', $c) }}"
                                       class="text-sm font-bold text-slate-800 hover:text-orange-600 transition-colors">
                                        {{ $c->name }}
                                    </a>
                                    <p class="text-[9px] font-mono text-orange-500 font-bold mt-0.5">{{ $c->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 max-w-xs truncate italic">
                                {{ $c->description ?? '—' }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 text-slate-600 text-xs font-black">
                                {{ $c->documents_count ?? $c->documents()->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="editData = {{ $c->toJson() }}; editOpen = true"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </button>
                                @if(auth()->user()->hasRole('admin'))
                                <form action="{{ route('categories.destroy', $c) }}" method="POST"
                                      onsubmit="return confirm('Supprimer cette catégorie ?');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden divide-y divide-slate-50">
            @foreach($categories as $c)
            <div class="flex items-center gap-3 px-4 py-4">
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-folder-tree text-orange-500 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('categories.show', $c) }}"
                       class="text-sm font-bold text-slate-800 hover:text-orange-600 block truncate">{{ $c->name }}</a>
                    <p class="text-[9px] font-mono text-orange-500 mt-0.5">{{ $c->slug }}</p>
                </div>
                <button @click="editData = {{ $c->toJson() }}; editOpen = true"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all shrink-0">
                    <i class="fa-solid fa-pen text-xs"></i>
                </button>
            </div>
            @endforeach
        </div>

        @endif
    </div>

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
                    <textarea name="description" rows="3" placeholder="Description optionnelle..."
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
                    <textarea name="description" rows="3" :value="editData.description"
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
