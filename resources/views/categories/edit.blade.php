@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('categories.index') }}" class="text-gray-400 hover:text-orange-600 font-bold text-sm flex items-center gap-2 transition-all group">
            <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
            Retour à la gestion
        </a>
        <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest px-3 py-1 bg-gray-100 rounded-full">
            ID: #{{ $category->id }}
        </span>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <div class="p-10">
            <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                    <i class="fa-solid fa-pen-to-square text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-gray-900">Modifier la catégorie</h1>
                    <p class="text-gray-500 text-sm font-medium">Mise à jour de : <span class="text-orange-600 font-bold">{{ $category->name }}</span></p>
                </div>
            </div>

            <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Catégorie parente</label>
                    <select name="parent_id"
                            class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-gray-900 font-medium focus:bg-white focus:border-orange-500 focus:ring-0 transition-all">
                        <option value="">Aucune (catégorie racine)</option>
                        @foreach($allCategories as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }}
                        </option>
                        @endforeach
                    </select>
                    @if($category->parent)
                    <p class="text-[10px] text-slate-400 mt-1.5 ml-1">
                        Parent actuel : <span class="font-bold text-orange-500">{{ $category->parent->name }}</span>
                    </p>
                    @endif
                </div>

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nom de la catégorie</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                           class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-gray-900 font-bold focus:bg-white focus:border-orange-500 focus:ring-0 transition-all">
                    @error('name') <p class="text-red-500 text-xs mt-1 ml-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Slug (Identifiant URL)</label>
                    <div class="relative group">
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}" required
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-orange-600 font-mono text-sm font-bold focus:bg-white focus:border-orange-500 focus:ring-0 transition-all">
                        <i class="fa-solid fa-lock absolute right-6 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-orange-500 transition-colors"></i>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2 ml-1">Attention : modifier le slug peut casser les liens existants.</p>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-gray-900 font-medium focus:bg-white focus:border-orange-500 focus:ring-0 transition-all">{{ old('description', $category->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Conservation</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <input type="number" name="default_retention_years" min="0" max="100"
                                   value="{{ old('default_retention_years', $category->default_retention_years) }}"
                                   class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-5 py-3.5 text-gray-900 font-bold focus:bg-white focus:border-orange-500 focus:ring-0 transition-all placeholder:text-gray-300"
                                   placeholder="Durée (années)">
                            <p class="text-[10px] text-gray-400 mt-1 ml-1">Durée en années. Vide : celle de la catégorie parente.</p>
                        </div>
                        <div>
                            <select name="retention_trigger" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-4 py-3.5 text-sm text-gray-900 font-medium focus:bg-white focus:border-orange-500 focus:ring-0">
                                @foreach(\App\Models\Category::RETENTION_TRIGGERS as $value => $label)
                                <option value="{{ $value }}" @selected(old('retention_trigger', $category->retention_trigger ?? 'created') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1 ml-1">À compter de…</p>
                        </div>
                        <div>
                            <select name="final_disposition" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-4 py-3.5 text-sm text-gray-900 font-medium focus:bg-white focus:border-orange-500 focus:ring-0">
                                @foreach(\App\Models\Category::FINAL_DISPOSITIONS as $value => $label)
                                <option value="{{ $value }}" @selected(old('final_disposition', $category->final_disposition ?? 'review') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1 ml-1">Ensuite… (rien n'est détruit sans validation)</p>
                        </div>
                    </div>
                    @error('default_retention_years') <p class="text-red-500 text-xs mt-1 ml-1">{{ $message }}</p> @enderror
                    @if($category->documents()->exists())
                    <label class="flex items-start gap-2 mt-3 ml-1 text-xs text-gray-500 cursor-pointer">
                        <input type="checkbox" name="apply_retention_to_existing" value="1" class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        Appliquer aussi la nouvelle durée aux documents déjà rangés dans cette catégorie et ses sous-catégories
                        (sinon ils gardent la durée fixée à leur dépôt ; le point de départ et le sort final s'appliquent dans tous les cas).
                    </label>
                    @endif
                </div>

                @include('categories._workflow')

                @include('categories._access')

                <div class="pt-6 flex items-center justify-between border-t border-gray-50">
                    <button type="button" onclick="window.history.back()" class="text-gray-400 hover:text-red-500 font-bold text-sm transition-all flex items-center gap-2">
                        <i class="fa-solid fa-xmark"></i>
                        Abandonner
                    </button>

                    <button type="submit" 
                            class="bg-orange-600 hover:bg-orange-700 text-white px-10 py-4 rounded-2xl font-black shadow-lg shadow-orange-200 transition-all transform active:scale-95 flex items-center gap-3">
                        <i class="fa-solid fa-floppy-disk"></i>
                        ENREGISTRER LES MODIFICATIONS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // On garde le script de slug si tu veux qu'il se mette à jour, 
    // mais souvent en Edit, on préfère le laisser tel quel sauf si on l'efface.
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    nameInput.addEventListener('input', () => {
        // Optionnel : ne mettre à jour que si l'utilisateur change le nom
        // et qu'il souhaite que le slug suive.
    });
</script>
@endsection