{{-- Filtres de la liste des documents (App\Support\DocumentFilters) --}}
@php
    $creatorNames = $creators->pluck('full_name', 'id')->all();
    $chips = $filters->chips($creatorNames);
    $panelCount = $filters->panelCount();
    // Critères autres que le dossier ouvert (effacer les filtres garde le dossier)
    $hasCriteria = (bool) array_diff_key($filters->filterParams(), array_flip(['category', 'subcats', 'sort']));
    $keepFolder = $currentFolder ? ['category' => $currentFolder->id] : [];
    $isAdmin = auth()->user()->hasRole('admin');
    $selectClass = 'w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500';
    $labelClass = 'block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5';
@endphp
<div class="mb-6 space-y-3" x-data="{ panel: {{ $panelCount ? 'true' : 'false' }}, saving: {{ $errors->has('name') ? 'true' : 'false' }} }">

    {{-- Onglets rapides --}}
    <div class="flex items-center gap-1 overflow-x-auto pb-1 -mb-1">
        @foreach($scopes as $key => $scope)
        <a href="{{ $filters->url(['scope' => $key === 'all' ? null : $key, 'page' => null]) }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all
                  {{ $filters->scope() === $key ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-white hover:text-slate-800' }}">
            <i class="fa-solid {{ $scope['icon'] }} text-[10px]"></i> {{ $scope['label'] }}
        </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('documents.index') }}" class="bg-white border border-slate-100 rounded-2xl shadow-sm">
        @if($filters->get('scope'))<input type="hidden" name="scope" value="{{ $filters->get('scope') }}">@endif

        {{-- Recherche + actions --}}
        <div class="flex flex-col sm:flex-row gap-2 p-4">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                <input type="text" name="q" value="{{ $filters->get('q') }}"
                       placeholder="{{ $currentFolder ? 'Rechercher dans « ' . $currentFolder->name . ' »…' : 'Titre, référence, contenu… — &quot;expression exacte&quot; entre guillemets' }}"
                       class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-4 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
            </div>
            <div class="flex gap-2">
                <button type="button" @click="panel = !panel"
                        :class="panel ? 'bg-orange-50 border-orange-200 text-orange-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'"
                        class="inline-flex items-center gap-2 border px-4 py-2.5 rounded-xl text-xs font-bold transition-all">
                    <i class="fa-solid fa-sliders text-[10px]"></i> Filtres
                    @if($panelCount)<span class="bg-orange-500 text-white text-[9px] font-black rounded-full px-1.5 py-0.5 leading-none">{{ $panelCount }}</span>@endif
                </button>
                <select name="sort" onchange="this.form.submit()" class="bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    @foreach(\App\Support\DocumentFilters::SORTS as $value => $label)
                    @continue($value === 'relevance' && !$searchTerms)
                    <option value="{{ $value }}" {{ $sort === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2.5 rounded-xl text-xs font-black transition-all active:scale-95" title="Rechercher">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>

        {{-- Panneau de filtres --}}
        <div x-show="panel" x-cloak x-transition.opacity class="border-t border-slate-50 p-4 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="{{ $labelClass }}">Dossier</label>
                    <select name="category" class="{{ $selectClass }}">
                        <option value="">Tous</option>
                        <option value="none" {{ $filters->get('category') === 'none' ? 'selected' : '' }}>Sans dossier</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $filters->get('category') === $cat->id ? 'selected' : '' }}>{{ str_repeat('— ', $cat->depth ?? 0) }}{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-1.5 mt-1.5 text-[10px] text-slate-500 cursor-pointer">
                        <input type="hidden" name="subcats" value="0">
                        <input type="checkbox" name="subcats" value="1" {{ $filters->get('subcats') ? 'checked' : '' }} class="rounded border-slate-300 text-orange-600 focus:ring-orange-500 w-3 h-3">
                        Inclure les sous-dossiers
                    </label>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Statut</label>
                    <select name="status" class="{{ $selectClass }}">
                        <option value="">Tous</option>
                        @foreach(\App\Support\DocumentFilters::STATUSES as $value => $label)
                        <option value="{{ $value }}" {{ $filters->get('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Auteur</label>
                    <select name="creator" class="{{ $selectClass }}">
                        <option value="">Tous</option>
                        <option value="me" {{ $filters->get('creator') === 'me' ? 'selected' : '' }}>Moi</option>
                        @foreach($creators as $creator)
                        @continue($creator->id === auth()->id())
                        <option value="{{ $creator->id }}" {{ $filters->get('creator') === $creator->id ? 'selected' : '' }}>{{ $creator->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Type de fichier</label>
                    <select name="type" class="{{ $selectClass }}">
                        <option value="">Tous</option>
                        @foreach(\App\Support\FileType::FAMILIES as $value => $family)
                        <option value="{{ $value }}" {{ $filters->get('type') === $value ? 'selected' : '' }}>{{ $family['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Mots-clés</label>
                    <input type="text" name="tags" value="{{ $filters->get('tags') }}" list="tag-suggestions" placeholder="Ex : factures, 2026" class="{{ $selectClass }}">
                    <datalist id="tag-suggestions">
                        @foreach($tagSuggestions as $tag)<option value="{{ $tag }}">@endforeach
                    </datalist>
                </div>
                <div x-data="{ from: @js($filters->get('date_from')), to: @js($filters->get('date_to')),
                               preset(days) { const d = new Date(); this.to = ''; d.setDate(d.getDate() - days); this.from = d.toISOString().slice(0, 10); },
                               year() { this.from = new Date().getFullYear() + '-01-01'; this.to = ''; } }">
                    <label class="{{ $labelClass }}">Ajoutés entre</label>
                    <div class="flex items-center gap-1.5">
                        <input type="date" name="date_from" x-model="from" class="{{ $selectClass }} px-2">
                        <input type="date" name="date_to" x-model="to" class="{{ $selectClass }} px-2">
                    </div>
                    <div class="flex gap-2 mt-1.5 text-[10px] font-bold text-orange-600">
                        <button type="button" @click="preset(7)" class="hover:underline">7 jours</button>
                        <button type="button" @click="preset(30)" class="hover:underline">30 jours</button>
                        <button type="button" @click="year()" class="hover:underline">Cette année</button>
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Confidentialité</label>
                    <select name="confidential" class="{{ $selectClass }}">
                        <option value="">Tous</option>
                        <option value="1" {{ $filters->get('confidential') === '1' ? 'selected' : '' }}>Confidentiels</option>
                        <option value="0" {{ $filters->get('confidential') === '0' ? 'selected' : '' }}>Non confidentiels</option>
                    </select>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Échéance</label>
                    <select name="expiry" class="{{ $selectClass }}">
                        <option value="">Toutes</option>
                        @foreach(\App\Support\DocumentFilters::EXPIRY as $value => $label)
                        <option value="{{ $value }}" {{ $filters->get('expiry') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3">
                <a href="{{ $filters->url(($currentFolder ? [] : ['category' => null, 'subcats' => null]) + ['status' => null, 'creator' => null, 'type' => null, 'tags' => null, 'confidential' => null, 'date_from' => null, 'date_to' => null, 'expiry' => null]) }}"
                   class="text-xs font-bold text-slate-400 hover:text-red-500">Réinitialiser les filtres</a>
                <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all active:scale-95">
                    Appliquer
                </button>
            </div>
        </div>
    </form>

    {{-- Résumé, filtres actifs et vues enregistrées --}}
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-black text-slate-700 mr-1">
            {{ number_format($documents->total(), 0, ',', ' ') }} document{{ $documents->total() > 1 ? 's' : '' }}
            @if($activeView)<span class="text-orange-600">· {{ $activeView->name }}</span>@endif
        </span>

        @if($filters->get('q'))
        <a href="{{ $filters->url(['q' => null, 'sort' => $sort === 'relevance' ? null : $sort]) }}"
           class="inline-flex items-center gap-1.5 bg-slate-900 text-white text-[10px] font-bold pl-2.5 pr-2 py-1 rounded-lg hover:bg-slate-700">
            « {{ \Illuminate\Support\Str::limit($filters->get('q'), 40) }} » <i class="fa-solid fa-xmark text-[9px]"></i>
        </a>
        @endif
        @foreach($chips as $chip)
        <a href="{{ $chip['remove'] }}" class="inline-flex items-center gap-1.5 bg-orange-50 border border-orange-100 text-orange-700 text-[10px] font-bold pl-2.5 pr-2 py-1 rounded-lg hover:bg-orange-100">
            {{ $chip['label'] }} <i class="fa-solid fa-xmark text-[9px]"></i>
        </a>
        @endforeach
        @if($hasCriteria || $filters->get('category') === 'none')
        <a href="{{ route('documents.index', $keepFolder) }}" class="text-[10px] font-bold text-slate-400 hover:text-red-500 ml-1">Tout effacer</a>
        @endif

        <div class="ml-auto flex items-center gap-2">
            {{-- Nombre par page --}}
            <select onchange="window.location = this.value" title="Documents par page"
                    class="bg-white border border-slate-200 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-500 focus:outline-none focus:ring-2 focus:ring-orange-500">
                @foreach(\App\Support\DocumentFilters::PER_PAGE as $size)
                <option value="{{ $filters->url(['per_page' => $size === \App\Support\DocumentFilters::PER_PAGE[0] ? null : $size, 'sort' => $filters->get('sort')]) }}" {{ $filters->get('per_page') === $size ? 'selected' : '' }}>{{ $size }} / page</option>
                @endforeach
            </select>

            {{-- Vues enregistrées --}}
            @if($savedFilters->isNotEmpty())
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-[11px] font-bold px-3 py-1.5 rounded-lg">
                    <i class="fa-solid fa-bookmark text-[10px] text-orange-500"></i> Mes vues ({{ $savedFilters->count() }})
                    <i class="fa-solid fa-chevron-down text-[8px]"></i>
                </button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 top-full mt-1 w-72 bg-white border border-slate-100 rounded-xl shadow-xl z-40 py-1 max-h-80 overflow-y-auto">
                    @foreach($savedFilters as $saved)
                    <div class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 {{ $activeView?->id === $saved->id ? 'bg-orange-50' : '' }}">
                        <a href="{{ $saved->url() }}" class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800 truncate">{{ $saved->name }}</p>
                            @if($saved->is_shared)
                            <p class="text-[9px] text-slate-400"><i class="fa-solid fa-users text-[8px] mr-1"></i>Partagée{{ $saved->user_id !== auth()->id() ? ' par ' . ($saved->user?->full_name ?? 'un administrateur') : ' à l\'entreprise' }}</p>
                            @endif
                        </a>
                        @if($saved->canManage(auth()->user()))
                        <form method="POST" action="{{ route('saved-filters.destroy', $saved) }}" onsubmit="return confirm('Supprimer la vue « {{ addslashes($saved->name) }} » ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50" title="Supprimer">
                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($filters->isFiltered() && !$activeView)
            <button type="button" @click="saving = !saving"
                    class="inline-flex items-center gap-1.5 text-[11px] font-bold text-orange-600 hover:text-orange-700 px-2 py-1.5">
                <i class="fa-regular fa-bookmark text-[10px]"></i> Enregistrer cette vue
            </button>
            @endif
        </div>
    </div>

    {{-- Formulaire d'enregistrement de la vue --}}
    @if($filters->isFiltered() && !$activeView)
    <form x-show="saving" x-cloak method="POST" action="{{ route('saved-filters.store') }}"
          class="flex flex-col sm:flex-row sm:items-center gap-2 bg-orange-50/60 border border-orange-100 rounded-2xl p-3">
        @csrf
        <input type="hidden" name="query" value="{{ http_build_query($filters->filterParams()) }}">
        <input type="text" name="name" required maxlength="80" placeholder="Nom de la vue (ex : Factures à valider)"
               value="{{ old('name') }}" x-effect="saving && $nextTick(() => $el.focus())"
               class="flex-1 bg-white border border-orange-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
        @if($isAdmin)
        <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-600 cursor-pointer">
            <input type="checkbox" name="is_shared" value="1" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
            Partager à toute l'entreprise
        </label>
        @endif
        <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest">Enregistrer</button>
        @error('name')<p class="text-[10px] font-bold text-red-600 sm:ml-2">{{ $message }}</p>@enderror
    </form>
    @endif
</div>
