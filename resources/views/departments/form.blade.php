@extends('layouts.app')

@section('content')
@php
    $initialMembers = collect(old('members', $memberIds))->map(fn ($id) => (int) $id)->values();
    $initialAccess  = collect(old('access', $access))->filter()->all();
@endphp
<div x-data="departmentForm({
        users: @js($users),
        categories: @js($categories),
        suggested: @js($suggested),
        members: @js($initialMembers),
        managerId: @js(old('manager_id', $managerId) ? (int) old('manager_id', $managerId) : null),
        access: @js((object) $initialAccess),
     })"
     @beforeunload.window="if (dirty && !submitting) { $event.preventDefault(); $event.returnValue = ''; }"
     class="space-y-5">

    {{-- EN-TÊTE + résumé en direct --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
            <a href="{{ route('departments.index') }}" class="hover:text-orange-600 transition-colors">Services</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <span class="text-slate-600 font-bold">Configurer</span>
        </div>
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $department->name }}</h1>
                @if($department->description)
                <p class="text-xs text-slate-400 font-medium mt-1.5">{{ $department->description }}</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-100 shadow-sm text-[11px] font-bold text-slate-600">
                    <i class="fa-solid fa-user-group text-orange-500 text-[10px]"></i> <span x-text="members.length"></span> membre(s)
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-100 shadow-sm text-[11px] font-bold"
                      :class="manager ? 'text-slate-600' : 'text-amber-600'">
                    <i class="fa-solid fa-crown text-[10px]" :class="manager ? 'text-orange-500' : 'text-amber-500'"></i>
                    <span x-text="manager ? manager.name : 'Pas de responsable'"></span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-slate-100 shadow-sm text-[11px] font-bold text-slate-600">
                    <i class="fa-solid fa-folder-open text-orange-500 text-[10px]"></i> <span x-text="openCount"></span> catégorie(s) accessible(s)
                </span>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="flex items-start gap-3 bg-red-50 border border-red-100 rounded-2xl px-4 py-3 text-xs text-red-700">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    </div>
    @endif

    <form id="department-form" method="POST" action="{{ route('departments.update', $department) }}" @submit="submitting = true"
          class="grid grid-cols-1 xl:grid-cols-12 gap-5 items-start">
        @csrf
        @method('PUT')

        {{-- Champs envoyés (calculés à partir de l'état de la page) --}}
        <input type="hidden" name="manager_id" :value="managerId ?? ''">
        <template x-for="id in members" :key="'m' + id"><input type="hidden" name="members[]" :value="id"></template>
        <template x-for="entry in accessEntries" :key="'a' + entry[0]">
            <input type="hidden" :name="'access[' + entry[0] + ']'" :value="entry[1]">
        </template>

        {{-- ================= ÉQUIPE ================= --}}
        <section class="xl:col-span-5 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-5 pt-5 pb-4 border-b border-slate-50">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Équipe</h2>
                <p class="text-[11px] text-slate-400 mt-1">Ajoutez les collaborateurs, puis cliquez sur <i class="fa-solid fa-crown text-[9px]"></i> pour désigner le responsable.</p>
            </div>

            <div class="p-5 space-y-4">
                {{-- Ajout rapide (recherche + clavier) --}}
                <div class="relative" @click.outside="open = false">
                    <i class="fa-solid fa-user-plus absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="text" x-model="query" x-ref="search"
                           @focus="open = true" @input="open = true; cursor = 0"
                           @keydown.arrow-down.prevent="cursor = Math.min(cursor + 1, candidates.length - 1)"
                           @keydown.arrow-up.prevent="cursor = Math.max(cursor - 1, 0)"
                           @keydown.enter.prevent="candidates[cursor] && add(candidates[cursor].id)"
                           @keydown.escape="open = false"
                           placeholder="Ajouter un collaborateur (nom ou email)…"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-10 pr-4 py-2.5 text-sm font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">

                    <div x-show="open && (query || candidates.length)" x-cloak x-transition.opacity
                         class="absolute z-20 mt-1.5 w-full bg-white border border-slate-100 rounded-xl shadow-xl overflow-hidden">
                        <template x-for="(user, i) in candidates" :key="user.id">
                            <button type="button" @click="add(user.id)" @mouseenter="cursor = i"
                                    class="w-full flex items-center gap-3 px-3 py-2 text-left" :class="cursor === i ? 'bg-orange-50' : ''">
                                <span class="w-7 h-7 rounded-full bg-slate-900 text-white text-[9px] font-black flex items-center justify-center shrink-0" x-text="user.initials"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-xs font-bold text-slate-800 truncate" x-text="user.name"></span>
                                    <span class="block text-[10px] text-slate-400 truncate" x-text="user.email"></span>
                                </span>
                                <span x-show="!user.services.length" class="text-[8px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 rounded px-1.5 py-0.5 shrink-0">Sans service</span>
                                <span x-show="user.services.length" class="text-[9px] text-slate-400 truncate max-w-[7rem] shrink-0" x-text="user.services.join(', ')"></span>
                            </button>
                        </template>
                        <p x-show="!candidates.length" class="px-3 py-3 text-xs text-slate-400">Aucun collaborateur à ajouter.</p>
                    </div>
                </div>

                {{-- Raccourci : tous ceux qui n'ont encore aucun service --}}
                <button type="button" x-show="unassigned.length" x-cloak @click="unassigned.forEach(u => add(u.id, false))"
                        class="w-full flex items-center justify-between gap-3 px-3.5 py-2.5 rounded-xl border border-dashed border-amber-200 bg-amber-50/60 hover:bg-amber-50 text-left transition-all">
                    <span class="text-[11px] text-amber-800">
                        <i class="fa-solid fa-user-clock mr-1"></i>
                        <span class="font-black" x-text="unassigned.length"></span> collaborateur(s) sans service
                    </span>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-700">Tout ajouter</span>
                </button>

                {{-- Membres --}}
                <div class="space-y-1.5">
                    <template x-for="user in memberList" :key="user.id">
                        <div class="group flex items-center gap-3 px-3 py-2 rounded-xl border transition-all"
                             :class="user.id === managerId ? 'border-orange-200 bg-orange-50/50' : 'border-slate-100 hover:border-slate-200'">
                            <span class="w-8 h-8 rounded-full text-white text-[10px] font-black flex items-center justify-center shrink-0"
                                  :class="user.inactive ? 'bg-slate-300' : 'bg-slate-900'" x-text="user.initials"></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-slate-800 truncate" x-text="user.name"></span>
                                    <span x-show="user.id === managerId" class="text-[8px] font-black uppercase tracking-wider text-orange-700 bg-orange-100 rounded px-1.5 py-0.5 shrink-0">Responsable</span>
                                    <span x-show="user.inactive" class="text-[8px] font-black uppercase tracking-wider text-slate-500 bg-slate-100 rounded px-1.5 py-0.5 shrink-0">Désactivé</span>
                                </span>
                                <span class="block text-[10px] text-slate-400 truncate">
                                    <span x-text="user.email"></span>
                                    <span x-show="user.viewer"> · lecteur (consultation seule)</span>
                                </span>
                            </span>
                            <button type="button" @click="toggleManager(user.id)"
                                    :title="user.id === managerId ? 'Retirer le rôle de responsable' : 'Désigner comme responsable'"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg transition-all"
                                    :class="user.id === managerId ? 'text-orange-500' : 'text-slate-300 hover:text-orange-500 hover:bg-orange-50'">
                                <i class="fa-solid fa-crown text-[11px]"></i>
                            </button>
                            <button type="button" @click="remove(user.id)" title="Retirer du service"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                    </template>

                    <div x-show="!members.length" class="text-center py-8 px-4 rounded-xl border border-dashed border-slate-200">
                        <i class="fa-solid fa-user-group text-slate-200 text-2xl"></i>
                        <p class="text-xs font-bold text-slate-500 mt-2">Aucun membre pour l'instant</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tapez un nom ci-dessus pour ajouter un collaborateur.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= ACCÈS ================= --}}
        <section class="xl:col-span-7 bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-5 pt-5 pb-4 border-b border-slate-50 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Documents accessibles</h2>
                    <p class="text-[11px] text-slate-400 mt-1">Un accès donné à une catégorie vaut aussi pour ses sous-catégories.</p>
                </div>
                <div class="flex gap-1.5 shrink-0">
                    <button type="button" @click="setAll('view')" class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-[10px] font-bold text-slate-600 transition-all">
                        <i class="fa-solid fa-eye text-[9px] mr-1"></i>Tout en consultation
                    </button>
                    <button type="button" @click="setAll('')" class="px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 text-[10px] font-bold text-slate-600 transition-all">
                        Tout retirer
                    </button>
                </div>
            </div>

            <div class="p-5 space-y-4">
                @if($categories->isEmpty())
                <div class="text-center py-10">
                    <i class="fa-solid fa-folder-open text-slate-200 text-2xl"></i>
                    <p class="text-xs text-slate-400 mt-2">Aucune catégorie. <a href="{{ route('categories.index') }}" class="text-orange-600 font-bold hover:underline">Créer des catégories</a></p>
                </div>
                @else

                {{-- Suggestions selon le service --}}
                <div x-show="pendingSuggestions.length" x-cloak
                     class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-100">
                    <i class="fa-solid fa-wand-magic-sparkles text-orange-500 hidden sm:block"></i>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] font-black text-slate-800">Suggestions pour {{ $department->name }}</p>
                        <p class="text-[11px] text-slate-500 truncate" x-text="pendingSuggestions.map(c => c.name).join(', ')"></p>
                    </div>
                    <div class="flex gap-1.5 shrink-0">
                        <button type="button" @click="applySuggestions('view')" class="px-2.5 py-1.5 rounded-lg bg-white border border-orange-100 hover:border-orange-300 text-[10px] font-bold text-slate-700 transition-all">En consultation</button>
                        <button type="button" @click="applySuggestions('edit')" class="px-2.5 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-500 text-[10px] font-bold text-white transition-all">En modification</button>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="text" x-model="categoryQuery" placeholder="Rechercher une catégorie…"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-4 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div class="inline-flex bg-slate-100 rounded-xl p-0.5 shrink-0 self-start">
                        <button type="button" @click="onlyOpen = false" class="px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all" :class="!onlyOpen ? 'bg-white shadow-sm text-slate-800' : 'text-slate-400'">Toutes</button>
                        <button type="button" @click="onlyOpen = true" class="px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all" :class="onlyOpen ? 'bg-white shadow-sm text-slate-800' : 'text-slate-400'">
                            Avec accès <span class="ml-0.5 text-orange-600" x-text="openCount"></span>
                        </button>
                    </div>
                    <button type="button" x-show="!categoryQuery && !onlyOpen" @click="toggleAll()"
                            class="px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-[10px] font-bold text-slate-500 shrink-0 self-start transition-all"
                            x-text="allCollapsed ? 'Tout déplier' : 'Tout replier'"></button>
                </div>

                <div class="border border-slate-100 rounded-xl overflow-hidden divide-y divide-slate-50">
                    <template x-for="category in visibleCategories" :key="category.id">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 sm:gap-3 px-3 py-2 hover:bg-slate-50/60"
                             :class="effective(category.id) === 'edit' ? 'bg-orange-50/30' : ''">
                            <div class="flex items-center gap-1.5 min-w-0" :style="'padding-left:' + (categoryQuery ? 0 : category.depth * 1.25) + 'rem'">
                                <button type="button" x-show="hasChildren(category.id) && !categoryQuery" @click="toggleCollapse(category.id)"
                                        class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 shrink-0">
                                    <i class="fa-solid fa-chevron-down text-[8px] transition-transform" :class="collapsed[category.id] ? '-rotate-90' : ''"></i>
                                </button>
                                <span x-show="!hasChildren(category.id) || categoryQuery" class="w-5 shrink-0"></span>
                                <i class="fa-solid text-xs shrink-0" :class="[category.depth ? 'fa-folder' : 'fa-folder-tree', effective(category.id) ? 'text-orange-400' : 'text-slate-300']"></i>
                                <span class="text-xs font-bold truncate" :class="effective(category.id) ? 'text-slate-800' : 'text-slate-500'" x-text="category.name"></span>
                                {{-- Replié : nombre de sous-catégories et combien sont accessibles --}}
                                <span x-show="collapsed[category.id] && !categoryQuery && !onlyOpen" class="hidden sm:inline text-[9px] font-bold text-slate-400 shrink-0"
                                      x-text="descendantsSummary(category.id)"></span>
                                <span x-show="isSuggested(category.id)" class="text-[8px] font-black uppercase tracking-wider text-orange-600 bg-orange-50 rounded px-1.5 py-0.5 shrink-0">Suggéré</span>
                                <span x-show="category.public" class="text-[8px] font-black uppercase tracking-wider text-green-600 bg-green-50 rounded px-1.5 py-0.5 shrink-0" title="Consultable par toute l'entreprise">Tous</span>
                            </div>

                            {{-- Accès hérité en modification : rien à régler ici --}}
                            <span x-show="inherited(category.id) === 'edit'" class="text-[10px] font-bold text-orange-600 shrink-0 self-end sm:self-auto">
                                <i class="fa-solid fa-arrow-turn-up rotate-90 text-[8px] mr-1"></i>Modification héritée
                            </span>

                            <div x-show="inherited(category.id) !== 'edit'" class="inline-flex bg-slate-100 rounded-lg p-0.5 shrink-0 self-end sm:self-auto">
                                <template x-for="option in options(category.id)" :key="option.value">
                                    <button type="button" @click="setLevel(category.id, option.value)"
                                            class="px-2.5 py-1 rounded-md text-[10px] font-bold transition-all"
                                            :class="(access[category.id] || '') === option.value
                                                ? 'bg-white shadow-sm ' + (option.value === 'edit' ? 'text-orange-600' : 'text-slate-800')
                                                : 'text-slate-400 hover:text-slate-600'"
                                            x-text="option.label"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <p x-show="!visibleCategories.length" class="px-4 py-6 text-center text-xs text-slate-400"
                       x-text="onlyOpen && !categoryQuery ? 'Ce service n\'a encore accès à aucune catégorie.' : 'Aucune catégorie ne correspond.'"></p>
                </div>

                <p class="text-[10px] text-slate-400">
                    <i class="fa-solid fa-lock text-[9px] mr-1"></i>
                    Les documents <span class="font-bold text-slate-500">confidentiels</span> restent réservés à leur auteur, aux administrateurs et aux partages explicites.
                </p>
                @endif
            </div>
        </section>

        {{-- Barre d'enregistrement (reste visible en bas de l'écran) --}}
        <div class="xl:col-span-12 sticky bottom-4 z-30">
            {{-- Marge à droite : laisse la place au bouton flottant « Guide » --}}
            <div class="sm:mr-32">
                <div class="flex items-center justify-between gap-3 bg-slate-900 text-white rounded-2xl shadow-2xl px-4 py-3 transition-all"
                     :class="dirty ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-90'">
                    <p class="text-xs font-bold flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full shrink-0" :class="dirty ? 'bg-orange-400 animate-pulse' : 'bg-emerald-400'"></span>
                        <span class="truncate hidden sm:inline" x-text="dirty ? 'Modifications non enregistrées' : 'Tout est enregistré'"></span>
                    </p>
                    <div class="flex gap-2 shrink-0">
                        <button type="button" x-show="dirty" @click="reset()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-white/10 transition-all">Annuler</button>
                        <a x-show="!dirty" href="{{ route('departments.index') }}" class="hidden sm:block px-4 py-2 rounded-xl text-xs font-bold text-slate-300 hover:text-white hover:bg-white/10 transition-all">Retour</a>
                        <button type="submit" :disabled="!dirty"
                                class="px-5 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 disabled:opacity-40 disabled:hover:bg-orange-600 text-xs font-black uppercase tracking-wider transition-all">
                            <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function departmentForm(config) {
    const rank = { '': 0, view: 1, edit: 2 };
    const normalize = s => (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const snapshot = s => JSON.stringify([[...s.members].sort((a, b) => a - b), s.managerId, Object.entries(s.access).filter(([, l]) => l).sort()]);

    return {
        users: config.users,
        categories: config.categories,
        suggested: config.suggested,
        members: [...config.members],
        managerId: config.managerId,
        access: { ...config.access },
        initial: null,
        query: '', open: false, cursor: 0,
        categoryQuery: '',
        onlyOpen: false,
        collapsed: {},
        submitting: false,

        init() {
            this.initial = { members: [...this.members], managerId: this.managerId, access: { ...this.access } };
            this.initialSnapshot = snapshot(this.initial);
            // Arbre replié par défaut (les sous-catégories héritent du parent),
            // sauf les branches où une sous-catégorie a un accès propre
            this.categories.filter(c => this.hasChildren(c.id)).forEach(c => {
                this.collapsed[c.id] = !this.descendants(c.id).some(d => this.access[d.id]);
            });
        },

        get dirty() { return snapshot(this) !== this.initialSnapshot; },
        get byId() { return Object.fromEntries(this.users.map(u => [u.id, u])); },
        get manager() { return this.managerId ? this.byId[this.managerId] : null; },

        // ----- Équipe -----
        get memberList() {
            return this.members.map(id => this.byId[id]).filter(Boolean)
                .sort((a, b) => (b.id === this.managerId) - (a.id === this.managerId) || a.name.localeCompare(b.name));
        },
        get candidates() {
            const q = normalize(this.query);
            return this.users
                .filter(u => !this.members.includes(u.id) && !u.inactive)
                .filter(u => !q || normalize(u.name + ' ' + u.email).includes(q))
                // Les collaborateurs sans service d'abord
                .sort((a, b) => (a.services.length > 0) - (b.services.length > 0) || a.name.localeCompare(b.name))
                .slice(0, 8);
        },
        get unassigned() { return this.users.filter(u => !u.services.length && !u.inactive && !this.members.includes(u.id)); },
        add(id, focus = true) {
            if (!this.members.includes(id)) this.members.push(id);
            this.query = ''; this.cursor = 0;
            if (focus) this.$nextTick(() => this.$refs.search.focus());
        },
        remove(id) {
            this.members = this.members.filter(m => m !== id);
            if (this.managerId === id) this.managerId = null;
        },
        toggleManager(id) { this.managerId = this.managerId === id ? null : id; },

        // ----- Accès -----
        get categoryById() { return Object.fromEntries(this.categories.map(c => [c.id, c])); },
        hasChildren(id) { return this.categories.some(c => c.parentId === id); },
        toggleCollapse(id) { this.collapsed[id] = !this.collapsed[id]; },
        // Niveau le plus élevé donné à un parent (hérité par la catégorie)
        inherited(id) {
            let level = '', parent = this.categoryById[id]?.parentId;
            while (parent) {
                const own = this.access[parent] || '';
                if (rank[own] > rank[level]) level = own;
                parent = this.categoryById[parent]?.parentId;
            }
            return level;
        },
        effective(id) {
            const own = this.access[id] || '', inherited = this.inherited(id);
            return rank[own] >= rank[inherited] ? own : inherited;
        },
        options(id) {
            return this.inherited(id) === 'view'
                ? [{ value: '', label: 'Consultation (héritée)' }, { value: 'edit', label: 'Modification' }]
                : [{ value: '', label: 'Aucun' }, { value: 'view', label: 'Consultation' }, { value: 'edit', label: 'Modification' }];
        },
        setLevel(id, level) { this.access[id] = level; },
        descendants(id) {
            const children = this.categories.filter(c => c.parentId === id);
            return children.concat(...children.map(c => this.descendants(c.id)));
        },
        descendantsSummary(id) {
            const all = this.descendants(id), open = all.filter(c => this.effective(c.id)).length;
            return all.length + ' sous-cat.' + (open ? ' · ' + open + ' accessible(s)' : '');
        },
        get allCollapsed() { return this.categories.filter(c => this.hasChildren(c.id)).every(c => this.collapsed[c.id]); },
        toggleAll() {
            const collapse = !this.allCollapsed;
            this.categories.filter(c => this.hasChildren(c.id)).forEach(c => this.collapsed[c.id] = collapse);
        },
        get visibleCategories() {
            const q = normalize(this.categoryQuery);
            if (q) return this.categories.filter(c => normalize(c.name).includes(q));
            if (this.onlyOpen) return this.categories.filter(c => this.effective(c.id));
            return this.categories.filter(c => {
                let parent = c.parentId;
                while (parent) { if (this.collapsed[parent]) return false; parent = this.categoryById[parent]?.parentId; }
                return true;
            });
        },
        get accessEntries() { return Object.entries(this.access).filter(entry => entry[1]); },
        get openCount() { return this.categories.filter(c => this.effective(c.id)).length; },
        setAll(level) {
            // Racines seulement : les sous-catégories héritent
            const access = {};
            if (level) this.categories.filter(c => !c.parentId).forEach(c => access[c.id] = level);
            this.access = access;
        },

        // ----- Suggestions -----
        isSuggested(id) { return this.suggested.includes(id); },
        get pendingSuggestions() { return this.categories.filter(c => this.isSuggested(c.id) && !this.effective(c.id)); },
        applySuggestions(level) { this.pendingSuggestions.forEach(c => this.access[c.id] = level); },

        reset() {
            this.members = [...this.initial.members];
            this.managerId = this.initial.managerId;
            this.access = { ...this.initial.access };
        },
    };
}
</script>
@endsection
