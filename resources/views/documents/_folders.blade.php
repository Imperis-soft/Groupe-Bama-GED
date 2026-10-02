{{-- Panneau « Dossiers » : arborescence des catégories (App\Support\CategoryTree) --}}
@php
    $canMove = auth()->user()->hasAnyRole(['admin', 'editor']);
    $activeId = is_int($filters->get('category')) ? $filters->get('category') : null;
    $openIds = collect($tree->path($activeId))->pluck('id')->all();
    $allUrl = $filters->url(['category' => null, 'subcats' => null, 'page' => null]);
@endphp
<div x-data="folderTree({ open: @js($openIds), storageKey: @js('ged.folders.' . auth()->id()) })"
     class="bg-white border border-slate-100 rounded-2xl shadow-sm p-3">
    <div class="flex items-center justify-between px-1 mb-2">
        <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dossiers</h2>
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('categories.index') }}" class="text-[10px] font-bold text-slate-400 hover:text-orange-600" title="Gérer les catégories">
            <i class="fa-solid fa-gear text-[10px]"></i>
        </a>
        @endif
    </div>

    @if($tree->roots()->count() > 6)
    <div class="relative mb-2">
        <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
        <input type="text" x-model="query" placeholder="Filtrer les dossiers…"
               class="w-full bg-slate-50 border border-slate-100 rounded-lg pl-7 pr-2 py-1.5 text-[11px] font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
    </div>
    @endif

    <a href="{{ $allUrl }}"
       class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs {{ $filters->get('category') === null ? 'bg-orange-50 font-black text-orange-700' : 'font-semibold text-slate-600 hover:bg-slate-50' }}">
        <i class="fa-solid fa-layer-group text-[11px] {{ $filters->get('category') === null ? 'text-orange-500' : 'text-slate-400' }}"></i>
        <span class="flex-1">Tous les documents</span>
    </a>

    @if($tree->isEmpty())
    <p class="px-2 py-3 text-[11px] text-slate-400">
        Aucun dossier.
        @if(auth()->user()->hasRole('admin'))<a href="{{ route('categories.index') }}" class="text-orange-600 font-bold hover:underline">Créer des catégories</a>@endif
    </p>
    @else
    <ul class="mt-0.5 max-h-[60vh] overflow-y-auto -mx-1 px-1">
        @foreach($tree->roots() as $node)
        @include('documents._folder-node', ['node' => $node, 'depth' => 0])
        @endforeach
    </ul>
    @endif

    @if($tree->uncategorizedCount())
    <a href="{{ $filters->url(['category' => 'none', 'subcats' => null, 'page' => null]) }}"
       class="mt-1 flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs border-t border-slate-50 {{ $filters->get('category') === 'none' ? 'bg-orange-50 font-black text-orange-700' : 'font-semibold text-slate-500 hover:bg-slate-50' }}">
        <i class="fa-regular fa-folder text-[11px] text-slate-300"></i>
        <span class="flex-1">Sans catégorie</span>
        <span class="text-[9px] font-bold text-slate-400">{{ $tree->uncategorizedCount() }}</span>
    </a>
    @endif

    @if($canMove && !$tree->isEmpty())
    <p class="mt-2 px-1 text-[9px] text-slate-300 leading-snug hidden lg:block">
        <i class="fa-solid fa-hand-pointer mr-1"></i>Glissez un document sur un dossier pour l'y ranger.
    </p>
    @endif
</div>

@once
<script>
function folderTree(config) {
    const read = () => { try { return JSON.parse(localStorage.getItem(config.storageKey) || '[]'); } catch (e) { return []; } };
    return {
        // Dossiers dépliés : ceux mémorisés + le chemin du dossier ouvert
        opened: [...new Set([...read(), ...config.open])],
        query: '',
        isOpen(id) { return this.opened.includes(id); },
        toggle(id) {
            this.opened = this.isOpen(id) ? this.opened.filter(i => i !== id) : [...this.opened, id];
            try { localStorage.setItem(config.storageKey, JSON.stringify(this.opened)); } catch (e) {}
        },
        fold(text) { return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },
        matches(el) { return !this.query.trim() || el.dataset.search.includes(this.fold(this.query.trim())); },
    };
}
</script>
@endonce
