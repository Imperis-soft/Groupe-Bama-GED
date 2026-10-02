{{-- Nœud de l'arbre des dossiers (récursif) --}}
@php
    $isActive = $filters->get('category') === $node->id;
    $hasChildren = $node->children->isNotEmpty();
    // Texte de recherche : nom du dossier et de tous ses sous-dossiers, sans accents
    $searchText = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii(
        collect($tree->descendantIds($node->id))->map(fn ($id) => $tree->get($id)?->name)->join(' ')
    ));
@endphp
<li data-search="{{ $searchText }}" x-show="matches($el)">
    <div class="group/node flex items-center gap-1 rounded-lg pr-1 transition-colors"
         :class="dropTarget === {{ $node->id }} ? 'bg-orange-100 ring-2 ring-orange-400' : '{{ $isActive ? 'bg-orange-50' : 'hover:bg-slate-50' }}'"
         style="padding-left: {{ 0.25 + $depth * 0.85 }}rem"
         @if($canMove)
         @dragover.prevent="dragIds.length && (dropTarget = {{ $node->id }})"
         @dragleave="dropTarget === {{ $node->id }} && (dropTarget = null)"
         @drop.prevent="dropOn({{ $node->id }}, @js($node->name))"
         @endif>
        @if($hasChildren)
        <button type="button" @click="toggle({{ $node->id }})" class="w-5 h-6 flex items-center justify-center text-slate-300 hover:text-slate-600 shrink-0"
                :aria-expanded="isOpen({{ $node->id }})">
            <i class="fa-solid fa-chevron-right text-[8px] transition-transform" :class="isOpen({{ $node->id }}) && 'rotate-90'"></i>
        </button>
        @else
        <span class="w-5 shrink-0"></span>
        @endif
        <a href="{{ $filters->url(['category' => $node->id, 'subcats' => null, 'page' => null]) }}"
           class="flex-1 min-w-0 flex items-center gap-2 py-1.5 text-xs {{ $isActive ? 'font-black text-orange-700' : 'font-semibold text-slate-600 group-hover/node:text-slate-900' }}">
            <i class="fa-solid text-[11px] shrink-0 {{ $isActive ? 'text-orange-500' : 'text-amber-400' }}"
               :class="isOpen({{ $node->id }}) && {{ $hasChildren ? 'true' : 'false' }} ? 'fa-folder-open' : 'fa-folder'"></i>
            <span class="truncate" title="{{ $node->name }}">{{ $node->name }}</span>
        </a>
        @if($node->total_count)
        <span class="text-[9px] font-bold {{ $isActive ? 'text-orange-600' : 'text-slate-400' }} shrink-0" title="{{ $node->total_count }} document(s), sous-dossiers compris">{{ $node->total_count }}</span>
        @endif
    </div>
    @if($hasChildren)
    <ul x-show="isOpen({{ $node->id }}) || query" x-cloak>
        @foreach($node->children as $child)
        @include('documents._folder-node', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    </ul>
    @endif
</li>
