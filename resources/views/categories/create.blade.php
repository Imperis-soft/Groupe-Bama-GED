@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('categories.index') }}" class="text-orange-600 hover:text-orange-700 font-bold text-sm flex items-center gap-2 transition-all">
            <i class="fa-solid fa-arrow-left"></i>
            Retour à la liste
        </a>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-10">
            <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600">
                    <i class="fa-solid fa-folder-plus text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900">Nouvelle Catégorie</h1>
                    <p class="text-gray-500 text-sm font-medium">Définissez un nouvel espace pour vos documents.</p>
                </div>
            </div>

            <form action="{{ route('categories.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Catégorie parente</label>
                    <select name="parent_id"
                            class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-gray-900 font-medium focus:bg-white focus:border-orange-500 focus:ring-0 transition-all">
                        <option value="">Aucune (catégorie racine)</option>
                        @foreach($allCategories as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nom de la catégorie</label>
                    <input type="text" name="name" id="name" required
                           class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-gray-900 font-bold focus:bg-white focus:border-orange-500 focus:ring-0 transition-all placeholder:text-gray-300"
                           placeholder="ex: Ressources Humaines">
                    @error('name') <p class="text-red-500 text-xs mt-1 ml-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Slug (Identifiant URL)</label>
                    <div class="relative">
                        <input type="text" name="slug" id="slug" required
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-orange-600 font-mono text-sm font-bold focus:bg-white focus:border-orange-500 focus:ring-0 transition-all placeholder:text-gray-300"
                               placeholder="ressources-humaines">
                        <i class="fa-solid fa-link absolute right-6 top-1/2 -translate-y-1/2 text-gray-300"></i>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2 ml-1 italic">Généré automatiquement à partir du nom en minuscule avec des tirets.</p>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-gray-900 font-medium focus:bg-white focus:border-orange-500 focus:ring-0 transition-all placeholder:text-gray-300"
                              placeholder="Décrivez brièvement les documents contenus dans cette catégorie..."></textarea>
                </div>

                <div class="pt-4 flex items-center justify-end gap-4">
                    <a href="{{ route('categories.index') }}" class="text-gray-400 hover:text-gray-600 font-bold text-sm transition-all">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="bg-orange-600 hover:bg-orange-700 text-white px-10 py-4 rounded-2xl font-black shadow-lg shadow-orange-200 transition-all transform active:scale-95 flex items-center gap-3">
                        <i class="fa-solid fa-check"></i>
                        CRÉER LA CATÉGORIE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    nameInput.addEventListener('input', () => {
        const value = nameInput.value;
        slugInput.value = value
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-0]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    });
</script>
@endsection