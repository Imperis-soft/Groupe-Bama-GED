@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Catégories</h1>
            <p class="text-gray-500 font-medium text-sm">Structurez l'organisation de vos documents officiels.</p>
        </div>

        <button onclick="openModal('modalCreate')" 
           class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-2xl text-sm font-bold shadow-lg shadow-orange-200 transition-all flex items-center justify-center gap-2 self-start transform active:scale-95">
            <i class="fa-solid fa-folder-plus"></i>
            Nouvelle Catégorie
        </button>
    </div>

    @if(session('success'))
    <div class="mb-8 flex items-center p-4 bg-orange-50 text-orange-700 rounded-2xl border border-orange-100 animate-fade-in">
        <i class="fa-solid fa-check-circle mr-3 text-lg"></i>
        <span class="font-bold text-sm">{{ session('success') }}</span>
    </div>
    @endif

    <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-8 py-5 text-[11px] font-black text-gray-400 uppercase tracking-widest">Nom & Slug</th>
                    <th class="px-8 py-5 text-[11px] font-black text-gray-400 uppercase tracking-widest">Description</th>
                    <th class="px-8 py-5 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($categories as $c)
                <tr class="hover:bg-orange-50/20 transition-all group">
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition-all duration-300 shadow-sm">
                                <i class="fa-solid fa-folder-tree text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 leading-none text-sm"><a href="{{ route('categories.show', $c) }}" class="hover:underline">{{ $c->name }}</a></h3>
                                <p class="font-mono text-[9px] text-orange-500 mt-1 font-bold uppercase tracking-tighter">{{ $c->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <p class="text-xs text-gray-500 font-medium leading-relaxed max-w-md italic">
                            {{ Str::limit($c->description ?? 'Aucune description.', 60) }}
                        </p>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex justify-end gap-2">
                            <button onclick="openEditModal({{ $c->toJson() }})" 
                               class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 hover:bg-blue-50 hover:text-blue-600 transition-all shadow-sm">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                            </button>

                            <form action="{{ route('categories.destroy', $c) }}" method="POST" onsubmit="return confirm('Supprimer ?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-all shadow-sm">
                                    <i class="fa-solid fa-trash-can text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="modalCreate" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" onclick="closeModal('modalCreate')"></div>
        <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-[2.5rem] shadow-2xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-modal-enter">
            <div class="p-10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center text-orange-600">
                        <i class="fa-solid fa-folder-plus text-xl"></i>
                    </div>
                    <h2 class="text-xl font-black text-gray-900">Nouvelle Catégorie</h2>
                </div>
                <form action="{{ route('categories.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nom</label>
                        <input type="text" name="name" id="create_name" required oninput="generateSlug(this, 'create_slug')"
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-5 py-3 text-sm font-bold focus:bg-white focus:border-orange-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Slug</label>
                        <input type="text" name="slug" id="create_slug" required
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-5 py-3 text-[11px] font-mono text-orange-600 focus:bg-white focus:border-orange-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Description</label>
                        <textarea name="description" rows="3" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-5 py-3 text-sm focus:bg-white focus:border-orange-500 transition-all"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="closeModal('modalCreate')" class="text-xs font-bold text-gray-400 hover:text-gray-600">Annuler</button>
                        <button type="submit" class="bg-orange-600 text-white px-8 py-3 rounded-xl font-black text-xs shadow-lg shadow-orange-100 hover:bg-orange-700 transition-all">CRÉER</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="modalEdit" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" onclick="closeModal('modalEdit')"></div>
        <div class="inline-block overflow-hidden text-left align-middle transition-all transform bg-white rounded-[2.5rem] shadow-2xl sm:max-w-lg sm:w-full animate-modal-enter">
            <div class="p-10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                        <i class="fa-solid fa-pen-to-square text-xl"></i>
                    </div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Modifier la catégorie</h2>
                </div>
                <form id="formEdit" method="POST" class="space-y-5">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Nom</label>
                        <input type="text" name="name" id="edit_name" required
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-5 py-3 text-sm font-bold focus:bg-white focus:border-orange-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Slug</label>
                        <input type="text" name="slug" id="edit_slug" required
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-5 py-3 text-[11px] font-mono text-orange-600 focus:bg-white focus:border-orange-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-5 py-3 text-sm focus:bg-white focus:border-orange-500 transition-all"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="closeModal('modalEdit')" class="text-xs font-bold text-gray-400 hover:text-gray-600">Annuler</button>
                        <button type="submit" class="bg-orange-600 text-white px-8 py-3 rounded-xl font-black text-xs shadow-lg shadow-orange-100 hover:bg-orange-700 transition-all uppercase">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function generateSlug(input, targetId) {
        const value = input.value;
        document.getElementById(targetId).value = value
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    function openEditModal(category) {
        // Remplir les champs
        document.getElementById('edit_name').value = category.name;
        document.getElementById('edit_slug').value = category.slug;
        document.getElementById('edit_description').value = category.description || '';
        
        // Mettre à jour l'action du formulaire (URL dynamique)
        const form = document.getElementById('formEdit');
        form.action = `/categories/${category.id}`;
        
        openModal('modalEdit');
    }
</script>

<style>
    @keyframes modal-enter {
        from { opacity: 0; transform: scale(0.95) translateY(-20px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .animate-modal-enter { animation: modal-enter 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
</style>
@endsection