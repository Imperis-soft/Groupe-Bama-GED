@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto space-y-4" x-data="advancedSearch()">
    <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-slate-900 text-white rounded-lg flex items-center justify-center text-xs">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <div>
                <h1 class="text-sm font-black text-slate-900 leading-none">Moteur de recherche</h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Exploration avancée des archives</p>
            </div>
        </div>
        <a href="{{ route('documents.index') }}" class="text-[10px] font-black uppercase tracking-tighter text-slate-400 hover:text-orange-600 transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Retour
        </a>
    </div>

    {{-- Formulaire Compact --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-slate-50/50 border-b border-slate-100">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Critères de filtrage</h2>
        </div>
        
        <form @submit.prevent="performSearch" class="p-5 space-y-5">
            {{-- Ligne 1 : Mots-clés & Catégorie --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Mots-clés</label>
                    <div class="relative group">
                        <i class="fa-solid fa-quote-left absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                        <input type="text" x-model="searchQuery"
                               placeholder="Titre, référence..."
                               class="w-full pl-10 pr-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:ring-4 focus:ring-orange-500/5 focus:border-orange-500 transition-all outline-none border">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Catégorie</label>
                    <select x-model="selectedCategory" class="w-full px-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl focus:bg-white outline-none border transition-all">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Ligne 2 : Statut, Créateur, Confidentialité --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Statut</label>
                    <select x-model="selectedStatus" class="w-full px-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl outline-none border">
                        <option value="">Tous les statuts</option>
                        <option value="draft">Brouillon</option>
                        <option value="review">En révision</option>
                        <option value="approved">Approuvé</option>
                        <option value="archived">Archivé</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Créé par</label>
                    <select x-model="selectedCreator" class="w-full px-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl outline-none border">
                        <option value="">Tous les créateurs</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Confidentialité</label>
                    <select x-model="isConfidential" class="w-full px-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl outline-none border">
                        <option value="">Tous les niveaux</option>
                        <option value="1">Confidentiel</option>
                        <option value="0">Public</option>
                    </select>
                </div>
            </div>

            {{-- Ligne 3 : Dates & Tags --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Du (Date)</label>
                        <input type="date" x-model="dateFrom" class="w-full px-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl outline-none border">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Au (Date)</label>
                        <input type="date" x-model="dateTo" class="w-full px-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl outline-none border">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-500 uppercase mb-1.5 ml-1">Tags</label>
                    <input type="text" x-model="selectedTags" placeholder="Ex: finance, rnh" 
                           class="w-full px-4 py-2 text-xs font-bold bg-slate-50 border-slate-200 rounded-xl outline-none border">
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex pt-2 gap-3">
                <button type="button" @click="resetFilters" class="px-6 py-2.5 text-[10px] font-black uppercase text-slate-400 hover:text-red-500 transition-colors">
                    Effacer
                </button>
                <button type="submit" class="flex-1 bg-slate-900 text-white py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 shadow-lg shadow-slate-200 transition-all transform active:scale-[0.98]">
                    Lancer la recherche
                </button>
            </div>
        </form>
    </div>

    {{-- Résultats Épurés --}}
    <div x-show="results.length > 0 || searchPerformed" class="space-y-3" x-transition>
        <div class="flex items-center justify-between px-2">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Résultats trouvés : <span class="text-slate-900" x-text="results.length"></span>
            </h2>
            <div class="flex bg-white border border-slate-100 rounded-lg p-1">
                <button @click="toggleView('list')" :class="viewMode === 'list' ? 'bg-slate-100 text-slate-900 shadow-sm' : 'text-slate-400'" class="p-1.5 rounded-md transition-all">
                    <i class="fa-solid fa-list-ul text-[10px]"></i>
                </button>
                <button @click="toggleView('grid')" :class="viewMode === 'grid' ? 'bg-slate-100 text-slate-900 shadow-sm' : 'text-slate-400'" class="p-1.5 rounded-md transition-all">
                    <i class="fa-solid fa-table-cells-large text-[10px]"></i>
                </button>
            </div>
        </div>

        {{-- Vue Liste (Desktop) --}}
        <div x-show="viewMode === 'list'" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden hidden md:block">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Document</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Statut</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="doc in results" :key="doc.id">
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center text-orange-600">
                                        <i class="fa-solid fa-file-lines text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-900 leading-none mb-1" x-text="doc.title"></p>
                                        <p class="text-[9px] text-slate-400 font-mono" x-text="doc.reference"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded border border-slate-100" 
                                      :class="doc.status === 'approved' ? 'text-green-600 bg-green-50' : 'text-slate-400 bg-slate-50'"
                                      x-text="getStatusLabel(doc.status)"></span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a :href="'/documents/' + doc.id" class="text-xs font-black text-slate-900 hover:text-orange-600 uppercase tracking-tighter">Ouvrir</a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Vue Mobile / Grille --}}
        <div x-show="viewMode === 'grid' || window.innerWidth < 768" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="doc in results" :key="doc.id">
                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400">
                            <i class="fa-solid fa-file-word"></i>
                        </div>
                        <div>
                            <h3 class="text-[11px] font-black text-slate-900 truncate max-w-[120px]" x-text="doc.title"></h3>
                            <p class="text-[9px] text-slate-400 font-mono uppercase" x-text="doc.reference"></p>
                        </div>
                    </div>
                    <a :href="'/documents/' + doc.id" class="w-8 h-8 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 hover:bg-orange-600 hover:text-white transition-all">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                </div>
            </template>
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="results.length === 0 && searchPerformed" class="bg-white rounded-3xl p-12 text-center border border-slate-100 shadow-sm" x-transition>
        <div class="w-16 h-16 bg-slate-50 text-slate-200 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-ghost text-2xl"></i>
        </div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Aucune correspondance trouvée</p>
    </div>
</div>
@endsection