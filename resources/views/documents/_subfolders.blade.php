{{--
    Sous-dossiers du dossier ouvert en tuiles (à la racine : dossiers de premier niveau), affichés en navigation
    (aucun filtre autre que le dossier), et fenêtre créer / renommer (état « folderModal » de documentIndex).
--}}
@php
    $me = auth()->user();
    $browsing = !array_diff_key($filters->filterParams(), array_flip(['category', 'subcats']));
    $subfolders = $currentFolder ? $currentFolder->children : ($filters->get('category') === null ? $tree->roots() : collect());
    $canCreate = $filters->get('category') !== 'none' && $me->canCreateFolderIn($currentFolder?->id);
    $canManage = $currentFolder && $me->canManageFolder($currentFolder);
@endphp

@if($browsing && ($subfolders->isNotEmpty() || $canCreate))
<section class="mb-6" aria-label="Dossiers">
    <div class="flex items-center justify-between mb-2.5">
        <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
            Dossiers @if($subfolders->isNotEmpty())<span class="text-slate-300">· {{ $subfolders->count() }}</span>@endif
        </h2>
        @if($canCreate && $subfolders->isNotEmpty())
        <button type="button" @click="folderModal = 'create'" class="text-[11px] font-bold text-slate-400 hover:text-orange-600 transition-colors">
            <i class="fa-solid fa-folder-plus text-[10px] mr-1"></i>Nouveau dossier
        </button>
        @endif
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5">
        @foreach($subfolders as $folder)
        <a href="{{ $filters->url(['category' => $folder->id, 'subcats' => null, 'page' => null]) }}"
           class="group relative flex items-center gap-3 bg-white border rounded-xl px-3.5 py-3 shadow-sm transition-all"
           :class="dropTarget === {{ $folder->id }} ? 'border-orange-400 bg-orange-50 ring-2 ring-orange-200' : 'border-slate-100 hover:border-orange-200 hover:shadow-md'"
           @if($canMove)
           @dragover.prevent="dragIds.length && (dropTarget = {{ $folder->id }})"
           @dragleave="dropTarget === {{ $folder->id }} && (dropTarget = null)"
           @drop.prevent="dropOn({{ $folder->id }}, @js($folder->name))"
           @endif>
            <span class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0 group-hover:bg-amber-100 transition-colors">
                <i class="fa-solid fa-folder text-amber-400 text-lg"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block text-[13px] font-bold text-slate-800 group-hover:text-orange-700 truncate transition-colors" title="{{ $folder->name }}">{{ $folder->name }}</span>
                <span class="block text-[11px] text-slate-400 mt-0.5">
                    @if($folder->total_count){{ $folder->total_count }} doc{{ $folder->total_count > 1 ? 's' : '' }}@else Vide @endif
                    @if($folder->children->isNotEmpty())<span class="text-slate-300">·</span> {{ $folder->children->count() }} dossier{{ $folder->children->count() > 1 ? 's' : '' }}@endif
                </span>
            </span>
            <i class="fa-solid fa-chevron-right text-[9px] text-slate-200 group-hover:text-orange-400 transition-colors"></i>
        </a>
        @endforeach
        @if($canCreate && $subfolders->isEmpty())
        <button type="button" @click="folderModal = 'create'"
                class="group flex items-center gap-3 border border-dashed border-slate-200 hover:border-orange-300 hover:bg-orange-50/40 rounded-xl px-3.5 py-3 text-left transition-all">
            <span class="w-10 h-10 rounded-lg bg-slate-50 group-hover:bg-orange-100 flex items-center justify-center shrink-0 transition-colors">
                <i class="fa-solid fa-folder-plus text-slate-300 group-hover:text-orange-500 text-lg transition-colors"></i>
            </span>
            <span>
                <span class="block text-[13px] font-bold text-slate-500 group-hover:text-orange-700">Nouveau dossier</span>
                <span class="block text-[11px] text-slate-400 mt-0.5">{{ $currentFolder ? 'Dans « ' . $currentFolder->name . ' »' : 'À la racine' }}</span>
            </span>
        </button>
        @endif
    </div>
</section>
@endif

{{-- Fenêtre créer / renommer un dossier --}}
@if($canCreate || $canManage)
<div x-show="folderModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4" @keydown.escape.window="folderModal = null">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="folderModal = null"></div>
    <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden"
         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        @if($canCreate)
        <template x-if="folderModal === 'create'">
            <form method="POST" action="{{ route('folders.store') }}">
                @csrf
                <input type="hidden" name="_folder_mode" value="create">
                @if($currentFolder)<input type="hidden" name="parent_id" value="{{ $currentFolder->id }}">@endif
                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-folder-plus text-amber-500"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-black text-slate-900">Nouveau dossier</h2>
                            <p class="text-xs text-slate-400 mt-0.5 truncate">
                                {{ $currentFolder ? 'Dans ' . $tree->label($currentFolder->id) : 'À la racine du plan de classement' }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <input type="text" name="name" required maxlength="120" x-init="$nextTick(() => $el.focus())"
                               value="{{ old('_folder_mode') === 'create' ? old('name') : '' }}" placeholder="Nom du dossier"
                               class="w-full bg-slate-50 border {{ old('_folder_mode') === 'create' && $folderErrors->any() ? 'border-red-300' : 'border-slate-100' }} rounded-xl px-4 py-3 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @if(old('_folder_mode') === 'create')
                        @foreach($folderErrors->all() as $message)<p class="text-red-500 text-[11px] font-bold mt-1.5">{{ $message }}</p>@endforeach
                        @endif
                    </div>
                    <p class="flex gap-2 text-[11px] text-slate-500 bg-slate-50 rounded-xl px-3 py-2.5 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-slate-300 mt-0.5"></i>
                        @if($currentFolder)
                        <span>Le dossier reprend les accès et la durée de conservation de « {{ $currentFolder->name }} ».</span>
                        @else
                        <span>Définissez ensuite les services qui y ont accès et sa durée de conservation (Détails du dossier).</span>
                        @endif
                    </p>
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 bg-slate-50 border-t border-slate-100">
                    <button type="button" @click="folderModal = null" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-200/60 transition-colors">Annuler</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white text-xs font-black shadow-sm shadow-orange-200 transition-colors">Créer le dossier</button>
                </div>
            </form>
        </template>
        @endif
        @if($canManage)
        <template x-if="folderModal === 'rename'">
            <form method="POST" action="{{ route('folders.update', $currentFolder) }}">
                @csrf @method('PUT')
                <input type="hidden" name="_folder_mode" value="rename">
                <div class="p-6 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-pen text-amber-500 text-sm"></i>
                        </span>
                        <div>
                            <h2 class="text-base font-black text-slate-900">Renommer le dossier</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Les documents restent à leur place.</p>
                        </div>
                    </div>
                    <div>
                        <input type="text" name="name" required maxlength="120" x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                               value="{{ old('_folder_mode') === 'rename' ? old('name') : $currentFolder->name }}"
                               class="w-full bg-slate-50 border {{ old('_folder_mode') === 'rename' && $folderErrors->any() ? 'border-red-300' : 'border-slate-100' }} rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        @if(old('_folder_mode') === 'rename')
                        @foreach($folderErrors->all() as $message)<p class="text-red-500 text-[11px] font-bold mt-1.5">{{ $message }}</p>@endforeach
                        @endif
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 bg-slate-50 border-t border-slate-100">
                    <button type="button" @click="folderModal = null" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-200/60 transition-colors">Annuler</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white text-xs font-black shadow-sm shadow-orange-200 transition-colors">Renommer</button>
                </div>
            </form>
        </template>
        @endif
    </div>
</div>
@endif
