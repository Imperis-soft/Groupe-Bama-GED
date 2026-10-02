@extends('layouts.app')

@section('content')
<div class="space-y-5" x-data="advancedSearch()">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors">Documents</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Recherche avancée</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Recherche avancée</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Explorez l'ensemble des archives documentaires</p>
        </div>
        <a href="{{ route('documents.index') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    {{-- FORMULAIRE --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-50 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Critères de recherche</h2>
        </div>

        <form @submit.prevent="performSearch" class="p-5 space-y-4">

            {{-- Mots-clés + Catégorie --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Mots-clés</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="text" x-model="searchQuery"
                               placeholder="Titre, référence, contenu… &quot;expression exacte&quot;"
                               @keydown.enter.prevent="performSearch"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-4 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Catégorie</label>
                    <select x-model="selectedCategory"
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        <option value="">Toutes les catégories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Statut + Créateur + Confidentialité --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Statut</label>
                    <select x-model="selectedStatus"
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        <option value="">Tous les statuts</option>
                        <option value="draft">Brouillon</option>
                        <option value="review">En révision</option>
                        <option value="approved">Approuvé</option>
                        <option value="archived">Archivé</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Créé par</label>
                    <select x-model="selectedCreator"
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        <option value="">Tous les créateurs</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Confidentialité</label>
                    <select x-model="isConfidential"
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        <option value="">Tous</option>
                        <option value="1">Confidentiel</option>
                        <option value="0">Public</option>
                    </select>
                </div>
            </div>

            {{-- Dates + Tags --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Date de début</label>
                    <input type="date" x-model="dateFrom"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Date de fin</label>
                    <input type="date" x-model="dateTo"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Tags</label>
                    <input type="text" x-model="selectedTags" placeholder="finance, contrat..."
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
            </div>

            {{-- Boutons --}}
            <div class="flex items-center gap-3 pt-1">
                <button type="button" @click="resetFilters"
                    class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-red-500 transition-colors px-3 py-2.5">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i> Réinitialiser
                </button>
                <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest py-3 rounded-xl shadow-lg shadow-orange-200 transition-all">
                    <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                    Lancer la recherche
                </button>
            </div>
        </form>
    </div>

    {{-- LOADING --}}
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="flex items-center gap-3 text-slate-400">
            <div class="w-5 h-5 border-2 border-orange-200 border-t-orange-600 rounded-full animate-spin"></div>
            <span class="text-xs font-bold">Recherche en cours...</span>
        </div>
    </div>

    {{-- RÉSULTATS --}}
    <div x-show="!loading && searchPerformed" x-transition>

        {{-- Barre résultats --}}
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                <p class="text-xs font-black text-slate-700 uppercase tracking-widest">
                    <span x-text="total"></span> résultat<span x-show="total > 1">s</span>
                </p>
                <select x-model="sort" @change="performSearch()" x-show="total > 0"
                        class="ml-2 bg-white border border-slate-200 rounded-lg px-2 py-1 text-[11px] font-bold text-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="relevance" x-show="searchQuery.trim()">Pertinence</option>
                    <option value="recent">Plus récents</option>
                    <option value="oldest">Plus anciens</option>
                    <option value="updated">Modifiés récemment</option>
                    <option value="title">Titre (A→Z)</option>
                </select>
            </div>
            <div x-show="results.length > 0" class="flex bg-white border border-slate-200 rounded-xl p-1 gap-0.5">
                <button @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-orange-500 text-white shadow' : 'text-slate-400 hover:text-slate-600'"
                    class="px-3 py-1.5 rounded-lg transition-all text-xs">
                    <i class="fa-solid fa-list-ul"></i>
                </button>
                <button @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-orange-500 text-white shadow' : 'text-slate-400 hover:text-slate-600'"
                    class="px-3 py-1.5 rounded-lg transition-all text-xs">
                    <i class="fa-solid fa-grip"></i>
                </button>
            </div>
        </div>

        {{-- Empty state --}}
        <div x-show="results.length === 0"
             class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-folder-open text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucun document trouvé</p>
            <p class="text-xs text-slate-300 mt-1">Essayez d'élargir vos critères de recherche</p>
        </div>

        {{-- Vue liste --}}
        <div x-show="results.length > 0 && viewMode === 'list'"
             class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            {{-- Header --}}
            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-slate-50 border-b border-slate-100">
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Référence</div>
                <div class="col-span-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Titre</div>
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Catégorie</div>
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest">Statut</div>
                <div class="col-span-2 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</div>
            </div>

            <div class="divide-y divide-slate-50">
                <template x-for="doc in results" :key="doc.id">
                    <div class="group px-4 md:px-6 py-3.5 hover:bg-slate-50/60 transition-colors">

                        {{-- Desktop --}}
                        <div class="hidden md:grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2">
                                <span class="font-mono text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-lg" x-text="doc.reference"></span>
                            </div>
                            <div class="col-span-4 flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                                    <i class="fa-solid text-xs group-hover:text-white transition-colors" :class="fileIcon(doc.file_path)"></i>
                                </div>
                                <div class="min-w-0">
                                    <a :href="doc.url" class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate transition-colors block">
                                        <template x-for="(seg, i) in doc.title_segments" :key="i"><span :class="seg.hit && 'bg-amber-100 text-slate-900 rounded-sm px-0.5'" x-text="seg.text"></span></template>
                                    </a>
                                    <p x-show="doc.snippet" class="text-[11px] text-slate-500 leading-snug mt-0.5 line-clamp-2">
                                        <template x-for="(seg, i) in (doc.snippet || [])" :key="i"><span :class="seg.hit && 'bg-amber-100 text-slate-900 rounded-sm px-0.5 font-semibold'" x-text="seg.text"></span></template>
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">
                                        <span x-text="doc.creator?.full_name || '—'"></span> · <span x-text="formatDate(doc.created_at)"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-span-2">
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold"
                                      x-text="doc.category ? doc.category.name : 'Général'"></span>
                            </div>
                            <div class="col-span-2">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider"
                                      :class="{
                                          'bg-green-50 text-green-600': doc.status === 'approved',
                                          'bg-blue-50 text-blue-600':   doc.status === 'review',
                                          'bg-slate-100 text-slate-400': doc.status === 'archived',
                                          'bg-amber-50 text-amber-600':  doc.status === 'draft'
                                      }"
                                      x-text="getStatusLabel(doc.status)"></span>
                            </div>
                            <div class="col-span-2 flex items-center justify-end gap-1">
                                <a :href="doc.url"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-orange-600 hover:text-white text-slate-600 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all">
                                    <i class="fa-solid fa-eye text-[8px]"></i> Voir
                                </a>
                                <a :href="doc.edit_url" x-show="doc.can_edit"
                                   class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-orange-50 hover:text-orange-600 transition-all">
                                    <i class="fa-solid fa-pen text-[10px]"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Mobile --}}
                        <div class="md:hidden flex items-start gap-3">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                                <i class="fa-solid text-sm" :class="fileIcon(doc.file_path)"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a :href="doc.url" class="text-sm font-bold text-slate-800 hover:text-orange-600 block truncate">
                                    <template x-for="(seg, i) in doc.title_segments" :key="i"><span :class="seg.hit && 'bg-amber-100 rounded-sm'" x-text="seg.text"></span></template>
                                </a>
                                <p x-show="doc.snippet" class="text-[11px] text-slate-500 leading-snug mt-0.5 line-clamp-2">
                                    <template x-for="(seg, i) in (doc.snippet || [])" :key="i"><span :class="seg.hit && 'bg-amber-100 text-slate-900 rounded-sm font-semibold'" x-text="seg.text"></span></template>
                                </p>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="font-mono text-[9px] text-slate-400" x-text="doc.reference"></span>
                                    <span class="text-slate-200">•</span>
                                    <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase"
                                          :class="{
                                              'bg-green-50 text-green-600': doc.status === 'approved',
                                              'bg-blue-50 text-blue-600':   doc.status === 'review',
                                              'bg-amber-50 text-amber-600': doc.status === 'draft',
                                              'bg-slate-100 text-slate-400': doc.status === 'archived'
                                          }"
                                          x-text="getStatusLabel(doc.status)"></span>
                                </div>
                            </div>
                            <a :href="doc.url"
                               class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-orange-600 hover:text-white text-slate-500 transition-all shrink-0">
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </a>
                        </div>

                    </div>
                </template>
            </div>
        </div>

        {{-- Vue grille --}}
        <div x-show="results.length > 0 && viewMode === 'grid'"
             class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4">
            <template x-for="doc in results" :key="doc.id">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-orange-200 transition-all group overflow-hidden">
                    <div class="aspect-square bg-slate-50 flex items-center justify-center group-hover:bg-orange-50 transition-colors relative">
                        <i class="fa-solid text-4xl opacity-40 group-hover:opacity-100 transition-opacity" :class="fileIcon(doc.file_path)"></i>
                        <template x-if="doc.is_confidential">
                            <span class="absolute top-2 right-2 w-5 h-5 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-lock text-red-500 text-[8px]"></i>
                            </span>
                        </template>
                    </div>
                    <div class="p-3">
                        <a :href="doc.url"
                           class="text-[11px] font-black text-slate-800 hover:text-orange-600 block truncate leading-tight transition-colors"
                           x-text="doc.title"></a>
                        <p class="text-[9px] text-slate-400 font-mono mt-0.5 truncate" x-text="doc.reference"></p>
                        <div class="flex items-center justify-between mt-3 pt-2 border-t border-slate-50">
                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase"
                                  :class="{
                                      'bg-green-50 text-green-600': doc.status === 'approved',
                                      'bg-blue-50 text-blue-600':   doc.status === 'review',
                                      'bg-amber-50 text-amber-600': doc.status === 'draft',
                                      'bg-slate-100 text-slate-400': doc.status === 'archived'
                                  }"
                                  x-text="getStatusLabel(doc.status)"></span>
                            <a :href="doc.url"
                               class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-orange-600 hover:text-white text-slate-500 transition-all">
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="page < lastPage" class="flex justify-center mt-4">
            <button @click="loadMore()" :disabled="loadingMore"
                class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-5 py-2.5 rounded-xl shadow-sm transition-all disabled:opacity-60">
                <i class="fa-solid text-[10px]" :class="loadingMore ? 'fa-spinner fa-spin' : 'fa-chevron-down'"></i>
                Afficher plus (<span x-text="total - results.length"></span> restants)
            </button>
        </div>

    </div>

</div>

<script>
function advancedSearch() {
    const FIELDS = { searchQuery: 'q', selectedCategory: 'category', selectedStatus: 'status', selectedCreator: 'creator',
                     isConfidential: 'confidential', dateFrom: 'date_from', dateTo: 'date_to', selectedTags: 'tags', sort: 'sort' };
    return {
        searchQuery:     '',
        selectedCategory: '',
        selectedStatus:  '',
        selectedCreator: '',
        isConfidential:  '',
        dateFrom:        '',
        dateTo:          '',
        selectedTags:    '',
        sort:            '',
        results:         [],
        total:           0,
        page:            1,
        lastPage:        1,
        loading:         false,
        loadingMore:     false,
        searchPerformed: false,
        viewMode:        localStorage.getItem('searchViewMode') || 'list',

        init() {
            this.$watch('viewMode', v => localStorage.setItem('searchViewMode', v));
            // Recherche partagée par lien (ou venant de la palette Ctrl+K)
            const params = new URLSearchParams(location.search);
            Object.entries(FIELDS).forEach(([field, param]) => { if (params.has(param)) this[field] = params.get(param); });
            if ([...params.keys()].some(k => Object.values(FIELDS).includes(k))) this.performSearch();
        },

        params(page = 1) {
            const params = new URLSearchParams();
            Object.entries(FIELDS).forEach(([field, param]) => {
                const value = String(this[field] ?? '').trim();
                if (value !== '') params.append(param, value);
            });
            if (page > 1) params.append('page', page);
            return params;
        },

        async fetchPage(page) {
            const res = await fetch('{{ route('documents.api.search') }}?' + this.params(page).toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        },

        async performSearch() {
            this.loading         = true;
            this.searchPerformed = false;
            // Le tri « pertinence » n'a de sens qu'avec des mots-clés
            if (this.sort === 'relevance' && !this.searchQuery.trim()) this.sort = '';
            history.replaceState(null, '', location.pathname + (this.params().toString() ? '?' + this.params().toString() : ''));

            try {
                const data   = await this.fetchPage(1);
                this.results  = data.data || [];
                this.total    = data.total || 0;
                this.page     = data.current_page || 1;
                this.lastPage = data.last_page || 1;
                this.sort     = data.sort || this.sort;
            } catch (err) {
                console.error('Erreur recherche:', err);
                this.results = [];
                this.total   = 0;
            } finally {
                this.loading         = false;
                this.searchPerformed = true;
            }
        },

        async loadMore() {
            this.loadingMore = true;
            try {
                const data = await this.fetchPage(this.page + 1);
                this.results.push(...(data.data || []));
                this.page     = data.current_page;
                this.lastPage = data.last_page;
            } finally {
                this.loadingMore = false;
            }
        },

        resetFilters() {
            Object.keys(FIELDS).forEach(field => this[field] = '');
            this.results          = [];
            this.total            = 0;
            this.searchPerformed  = false;
            history.replaceState(null, '', location.pathname);
        },

        getStatusLabel(status) {
            const labels = { draft: 'Brouillon', review: 'Révision', approved: 'Approuvé', archived: 'Archivé' };
            return labels[status] || status;
        },

        formatDate(iso) {
            return iso ? new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '';
        },

        toggleView(mode) { this.viewMode = mode; }
    }
}
</script>
@endsection
