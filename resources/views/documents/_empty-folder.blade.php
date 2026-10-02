{{-- Dossier ouvert sans document : invitation à y déposer des fichiers (le glisser-déposer est géré par l'import multiple) --}}
@php $canAdd = auth()->user()->hasAnyRole(['admin', 'editor']) && auth()->user()->canFileInCategory($currentFolder->id); @endphp
<div class="p-4 md:p-6">
    <div class="flex flex-col items-center justify-center text-center rounded-2xl {{ $canAdd ? 'border-2 border-dashed border-slate-200 hover:border-orange-300 hover:bg-orange-50/30 cursor-pointer' : '' }} py-14 px-6 transition-colors"
         @if($canAdd) @click="uploadModal = true" @endif>
        <div class="relative mb-5">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center">
                <i class="fa-solid fa-folder-open text-amber-300 text-3xl"></i>
            </div>
            @if($canAdd)
            <span class="absolute -bottom-1.5 -right-1.5 w-7 h-7 rounded-full bg-orange-600 border-2 border-white flex items-center justify-center shadow">
                <i class="fa-solid fa-arrow-up text-white text-[10px]"></i>
            </span>
            @endif
        </div>
        <p class="text-sm font-black text-slate-700">« {{ $currentFolder->name }} » est vide</p>
        @if($canAdd)
        <p class="text-xs text-slate-400 mt-1 max-w-sm">Glissez vos fichiers n'importe où sur la page pour les ranger ici, ou choisissez une action.</p>
        <div class="mt-5 flex flex-wrap justify-center gap-2" @click.stop>
            <button type="button" @click="uploadModal = true"
                    class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all active:scale-95">
                <i class="fa-solid fa-cloud-arrow-up text-[11px]"></i> Importer des fichiers
            </button>
        </div>
        @else
        <p class="text-xs text-slate-400 mt-1">Aucun document ne vous est accessible dans ce dossier pour le moment.</p>
        @endif
    </div>
</div>
