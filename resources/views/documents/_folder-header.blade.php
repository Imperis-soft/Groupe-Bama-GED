{{-- En-tête de l'explorateur quand un dossier est ouvert : chemin, nom, règles effectives et actions --}}
@php
    $info = \App\Support\FolderInfo::for($currentFolder, $tree);
    $retention = $info->retention();
    $accessRules = $info->departments();
    $publicSource = $info->publicSource();
    $canManageFolder = auth()->user()->canManageFolder($currentFolder);
    $isEmptyFolder = $currentFolder->children->isEmpty() && !$currentFolder->documents()->withTrashed()->exists();
    $chip = 'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-semibold';
@endphp
<div class="min-w-0">
    <nav class="flex items-center flex-wrap gap-1 text-xs font-medium text-slate-400 mb-1.5" aria-label="Fil d'Ariane">
        <a href="{{ $filters->url(['category' => null, 'subcats' => null, 'page' => null]) }}" class="hover:text-orange-600 transition-colors">Documents</a>
        @foreach($folderPath as $crumb)
        @continue($loop->last)
        <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 mx-0.5"></i>
        <a href="{{ $filters->url(['category' => $crumb->id, 'subcats' => null, 'page' => null]) }}" class="hover:text-orange-600 transition-colors">{{ $crumb->name }}</a>
        @endforeach
    </nav>

    <div class="flex items-center gap-3 min-w-0">
        <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-folder-open text-amber-400 text-xl"></i>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none truncate" title="{{ $currentFolder->name }}">{{ $currentFolder->name }}</h1>
        <div class="flex items-center gap-0.5 shrink-0">
            <a href="{{ route('categories.show', $currentFolder) }}" title="Détails du dossier"
               class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-orange-600 hover:shadow-sm transition-all">
                <i class="fa-solid fa-circle-info text-sm"></i>
            </a>
            @if($canManageFolder)
            <button type="button" @click="folderModal = 'rename'" title="Renommer"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-orange-600 hover:shadow-sm transition-all">
                <i class="fa-solid fa-pen text-xs"></i>
            </button>
            @if($isEmptyFolder)
            <form method="POST" action="{{ route('folders.destroy', $currentFolder) }}"
                  @submit="confirm(@js('Supprimer le dossier vide « ' . $currentFolder->name . ' » ?')) || $event.preventDefault()">
                @csrf @method('DELETE')
                <button type="submit" title="Supprimer le dossier (vide)"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-red-600 hover:shadow-sm transition-all">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
            </form>
            @endif
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-1.5 mt-3">
        <span class="{{ $chip }} bg-white border border-slate-100 text-slate-600">
            <i class="fa-regular fa-file-lines text-slate-400 text-[10px]"></i>
            {{ $currentFolder->total_count }} document{{ $currentFolder->total_count > 1 ? 's' : '' }}
        </span>
        @if($retention['years'])
        <span class="{{ $chip }} bg-white border border-slate-100 text-slate-600"
              title="{{ \App\Models\Category::FINAL_DISPOSITIONS[$retention['disposition']] ?? '' }}{{ $retention['inherited'] ? ' — règle héritée de « ' . $retention['source']->name . ' »' : '' }}">
            <i class="fa-solid fa-hourglass-half text-slate-400 text-[10px]"></i>
            Conservation {{ $retention['years'] }} an{{ $retention['years'] > 1 ? 's' : '' }}
            <span class="text-slate-400 font-medium">· {{ \App\Models\Category::RETENTION_TRIGGERS_SHORT[$retention['trigger']] ?? '' }}</span>
        </span>
        @endif
        @if($publicSource)
        <span class="{{ $chip }} bg-emerald-50 text-emerald-700" title="Tous les utilisateurs peuvent consulter les documents non confidentiels">
            <i class="fa-solid fa-globe text-[10px]"></i> Public
        </span>
        @endif
        @if($accessRules->isNotEmpty())
        <span class="{{ $chip }} bg-white border border-slate-100 text-slate-600"
              title="{{ $accessRules->map(fn ($r) => $r['name'] . ' (' . ($r['level'] === 'edit' ? 'modification' : 'lecture') . ')')->join(', ') }}">
            <i class="fa-solid fa-user-group text-slate-400 text-[10px]"></i>
            {{ $accessRules->count() === 1 ? $accessRules->first()['name'] : $accessRules->count() . ' services' }}
        </span>
        @elseif(!$publicSource)
        <span class="{{ $chip }} bg-slate-100 text-slate-500" title="Aucun service n'a accès : chacun ne voit que ses documents et ceux partagés avec lui">
            <i class="fa-solid fa-lock text-[10px]"></i> Privé
        </span>
        @endif
    </div>
</div>
