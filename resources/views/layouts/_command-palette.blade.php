{{--
    Palette de recherche (Ctrl+K / ⌘K, ou « / ») : documents, catégories et raccourcis de navigation.
    Ouverture depuis n'importe où : window.dispatchEvent(new CustomEvent('open-command-palette'))
--}}
@php
    $user = auth()->user();
    $paletteActions = collect([
        ['label' => 'Tableau de bord',     'icon' => 'fa-chart-pie',          'url' => route('dashboard')],
        ['label' => 'À traiter',           'icon' => 'fa-inbox',              'url' => route('inbox.index')],
        ['label' => 'Documents',           'icon' => 'fa-folder-open',        'url' => route('documents.index')],
        ['label' => 'Recherche avancée',   'icon' => 'fa-magnifying-glass',   'url' => route('documents.advanced-search')],
        ['label' => 'Favoris',             'icon' => 'fa-star',               'url' => route('documents.favorites')],
        ['label' => 'Catégories',          'icon' => 'fa-folder-tree',        'url' => route('categories.index')],
        ['label' => 'Notifications',       'icon' => 'fa-bell',               'url' => route('notifications.index')],
        ['label' => 'Mon profil',          'icon' => 'fa-user',               'url' => route('profile.show')],
    ]);
    if ($user->hasAnyRole(['admin', 'editor'])) {
        $paletteActions->push(['label' => 'Importer des documents', 'icon' => 'fa-cloud-arrow-up', 'url' => route('documents.index') . '#import']);
    }
    if ($user->hasRole('admin')) {
        $paletteActions->push(['label' => 'Utilisateurs', 'icon' => 'fa-users', 'url' => route('users.index')]);
        if (\Illuminate\Support\Facades\Route::has('departments.index')) {
            $paletteActions->push(['label' => 'Services', 'icon' => 'fa-sitemap', 'url' => route('departments.index')]);
        }
        $paletteActions->push(['label' => 'Circuits d\'approbation', 'icon' => 'fa-diagram-next', 'url' => route('approval-templates.index')]);
        $paletteActions->push(['label' => 'Rapports', 'icon' => 'fa-chart-bar', 'url' => route('reports.index')]);
        $paletteActions->push(['label' => 'Corbeille', 'icon' => 'fa-trash-can', 'url' => route('trash.index')]);
    }
@endphp
<div x-data="commandPalette({
        url: @js(route('search.quick')),
        searchUrl: @js(route('documents.advanced-search')),
        actions: @js($paletteActions->values()),
        recentKey: @js('ged.recentSearches.' . $user->id),
     })"
     @keydown.window="onGlobalKey($event)"
     @open-command-palette.window="open()">

    <div x-show="isOpen" x-cloak class="fixed inset-0 z-[120] flex items-start justify-center p-4 pt-[10vh]"
         @keydown.escape.prevent.stop="close()">
        <div @click="close()" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div @click.stop class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[75vh]">
            {{-- Saisie --}}
            <div class="flex items-center gap-3 px-5 border-b border-slate-100">
                <i class="fa-solid text-slate-400" :class="loading ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                <input type="text" x-ref="input" x-model="query"
                       @input.debounce.200ms="search()"
                       @keydown.arrow-down.prevent="move(1)"
                       @keydown.arrow-up.prevent="move(-1)"
                       @keydown.enter.prevent="choose()"
                       placeholder="Rechercher un document, une référence, une catégorie…"
                       class="flex-1 border-0 focus:ring-0 py-4 text-sm font-medium text-slate-800 placeholder-slate-400 bg-transparent">
                <kbd class="hidden sm:inline text-[10px] font-bold text-slate-400 bg-slate-100 rounded px-1.5 py-0.5">Échap</kbd>
            </div>

            <div class="overflow-y-auto py-2" x-ref="list">
                {{-- Recherches récentes --}}
                <template x-if="!query.trim() && recent.length">
                    <div class="pb-1">
                        <p class="px-5 pt-1 pb-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Recherches récentes</p>
                        <template x-for="(term, i) in recent" :key="term">
                            <button type="button" @click="query = term; search()"
                                    class="w-full flex items-center gap-3 px-5 py-2 text-left text-sm text-slate-600 hover:bg-slate-50">
                                <i class="fa-solid fa-clock-rotate-left text-slate-300 text-xs w-4"></i>
                                <span x-text="term"></span>
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Documents --}}
                <template x-if="documents.length">
                    <div>
                        <p class="px-5 pt-1 pb-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest">
                            Documents <span class="text-slate-300" x-show="total > documents.length" x-text="'· ' + total + ' au total'"></span>
                        </p>
                        <template x-for="doc in documents" :key="'d' + doc.id">
                            <a :href="doc.url" @mouseenter="cursor = items.indexOf(doc)"
                               :class="items[cursor] === doc ? 'bg-orange-50' : ''"
                               class="flex items-start gap-3 px-5 py-2.5 cursor-pointer" :data-index="items.indexOf(doc)">
                                <i class="fa-solid text-base w-4 mt-0.5 shrink-0" :class="fileIcon(doc.file_path)"></i>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-bold text-slate-800 truncate">
                                            <template x-for="(seg, i) in doc.title_segments" :key="i"><span :class="seg.hit && 'bg-amber-100 text-slate-900 rounded-sm'" x-text="seg.text"></span></template>
                                        </p>
                                        <i x-show="doc.is_confidential" class="fa-solid fa-lock text-red-400 text-[10px] shrink-0"></i>
                                    </div>
                                    <p class="text-[10px] text-slate-400 truncate">
                                        <span class="font-mono" x-text="doc.reference"></span>
                                        <span x-show="doc.category" x-text="' · ' + (doc.category?.name || '')"></span>
                                    </p>
                                    <p x-show="doc.snippet" class="text-[11px] text-slate-500 leading-snug mt-0.5 line-clamp-1">
                                        <template x-for="(seg, i) in (doc.snippet || [])" :key="i"><span :class="seg.hit && 'bg-amber-100 text-slate-900 rounded-sm font-semibold'" x-text="seg.text"></span></template>
                                    </p>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>

                {{-- Catégories --}}
                <template x-if="categories.length">
                    <div class="pt-1">
                        <p class="px-5 pt-1 pb-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Catégories</p>
                        <template x-for="cat in categories" :key="'c' + cat.id">
                            <a :href="cat.url" @mouseenter="cursor = items.indexOf(cat)"
                               :class="items[cursor] === cat ? 'bg-orange-50' : ''"
                               class="flex items-center gap-3 px-5 py-2 cursor-pointer" :data-index="items.indexOf(cat)">
                                <i class="fa-solid fa-folder text-orange-400 text-sm w-4"></i>
                                <span class="text-sm font-semibold text-slate-700">
                                    <template x-for="(seg, i) in cat.name" :key="i"><span :class="seg.hit && 'bg-amber-100 rounded-sm'" x-text="seg.text"></span></template>
                                </span>
                            </a>
                        </template>
                    </div>
                </template>

                {{-- Raccourcis --}}
                <template x-if="matchingActions.length">
                    <div class="pt-1">
                        <p class="px-5 pt-1 pb-1.5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Aller à</p>
                        <template x-for="action in matchingActions" :key="'a' + action.label">
                            <a :href="action.url" @click="close()" @mouseenter="cursor = items.indexOf(action)"
                               :class="items[cursor] === action ? 'bg-orange-50' : ''"
                               class="flex items-center gap-3 px-5 py-2 cursor-pointer" :data-index="items.indexOf(action)">
                                <i class="fa-solid text-slate-400 text-sm w-4" :class="action.icon"></i>
                                <span class="text-sm font-semibold text-slate-700" x-text="action.label"></span>
                            </a>
                        </template>
                    </div>
                </template>

                {{-- Aucun résultat --}}
                <div x-show="query.trim() && searched && !loading && !documents.length && !categories.length"
                     class="px-5 py-8 text-center">
                    <p class="text-sm font-bold text-slate-500">Aucun document ne correspond</p>
                    <p class="text-xs text-slate-400 mt-1">Vérifiez l'orthographe ou essayez moins de mots.</p>
                </div>
            </div>

            {{-- Pied --}}
            <div class="flex items-center justify-between gap-3 px-5 py-2.5 border-t border-slate-100 bg-slate-50 text-[10px] text-slate-400">
                <span class="hidden sm:flex items-center gap-3">
                    <span><kbd class="font-bold bg-white border border-slate-200 rounded px-1">↑</kbd> <kbd class="font-bold bg-white border border-slate-200 rounded px-1">↓</kbd> naviguer</span>
                    <span><kbd class="font-bold bg-white border border-slate-200 rounded px-1">Entrée</kbd> ouvrir</span>
                </span>
                <a x-show="query.trim()" :href="searchUrl + '?q=' + encodeURIComponent(query.trim())" @click="remember()"
                   class="font-black text-orange-600 hover:underline ml-auto">
                    Recherche avancée <span x-show="total" x-text="'(' + total + ')'"></span> <i class="fa-solid fa-arrow-right text-[9px]"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function commandPalette(config) {
    // Par utilisateur : un poste partagé ne montre pas les recherches d'un collègue
    const RECENT_KEY = config.recentKey;
    const readRecent = () => { try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch (e) { return []; } };

    return {
        isOpen: false,
        query: '',
        documents: [],
        categories: [],
        total: 0,
        loading: false,
        searched: false,
        cursor: 0,
        recent: readRecent(),
        controller: null,
        searchUrl: config.searchUrl,

        get matchingActions() {
            const q = this.fold(this.query.trim());
            const actions = q ? config.actions.filter(a => this.fold(a.label).includes(q)) : config.actions.slice(0, 5);
            return actions.slice(0, 6);
        },
        // Ordre de navigation au clavier : documents, catégories, raccourcis
        get items() { return [...this.documents, ...this.categories, ...this.matchingActions]; },

        fold(text) { return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },

        onGlobalKey(e) {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName) || e.target.isContentEditable;
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.isOpen ? this.close() : this.open();
            } else if (e.key === '/' && !typing && !this.isOpen) {
                e.preventDefault();
                this.open();
            }
        },

        open() {
            this.isOpen = true;
            this.recent = readRecent();
            this.cursor = 0;
            this.$nextTick(() => { this.$refs.input.focus(); this.$refs.input.select(); });
        },

        close() { this.isOpen = false; },

        async search() {
            const q = this.query.trim();
            this.cursor = 0;
            this.controller?.abort();
            if (q.length < 2) {
                this.documents = []; this.categories = []; this.total = 0; this.searched = false; this.loading = false;
                return;
            }
            this.controller = new AbortController();
            this.loading = true;
            try {
                const res = await fetch(config.url + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this.controller.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (q !== this.query.trim()) return; // réponse périmée
                this.documents = data.documents || [];
                this.categories = data.categories || [];
                this.total = data.total || 0;
                this.searched = true;
            } catch (e) {
                if (e.name !== 'AbortError') { this.documents = []; this.categories = []; this.searched = true; }
            } finally {
                if (q === this.query.trim()) this.loading = false;
            }
        },

        move(step) {
            if (!this.items.length) return;
            this.cursor = (this.cursor + step + this.items.length) % this.items.length;
            this.$nextTick(() => this.$refs.list.querySelector('[data-index="' + this.cursor + '"]')?.scrollIntoView({ block: 'nearest' }));
        },

        choose() {
            const item = this.items[this.cursor];
            this.remember();
            if (item?.url) {
                window.location.href = item.url;
            } else if (this.query.trim()) {
                window.location.href = this.searchUrl + '?q=' + encodeURIComponent(this.query.trim());
            }
            if (item?.url?.endsWith('#import')) this.close();
        },

        remember() {
            const q = this.query.trim();
            if (q.length < 2) return;
            this.recent = [q, ...readRecent().filter(t => t.toLowerCase() !== q.toLowerCase())].slice(0, 5);
            try { localStorage.setItem(RECENT_KEY, JSON.stringify(this.recent)); } catch (e) {}
        },
    };
}
</script>
