@extends('layouts.app')

@section('content')
@php
    $currentVersion = $document->version;
    $totalVersions  = $versions->total();
@endphp

<div class="space-y-5" x-data="{ uploadModal: false, dragging: false, fileName: '' }">

    {{-- Breadcrumb + Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors">Documents</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors truncate max-w-[160px]">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Versions</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Historique des versions</h1>
            <p class="text-xs text-slate-400 mt-1">
                <span class="font-bold text-slate-600">{{ $document->reference }}</span>
                &nbsp;·&nbsp; {{ $totalVersions }} version{{ $totalVersions > 1 ? 's' : '' }}
                &nbsp;·&nbsp; Version actuelle : <span class="font-black text-orange-600">v{{ $currentVersion }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            @if($document->canEdit())
            <button @click="uploadModal = true"
                class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black px-4 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
                <i class="fa-solid fa-upload text-[10px]"></i> Nouvelle version
            </button>
            @endif
            <a href="{{ route('documents.show', $document) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-code-branch text-orange-500 text-sm"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Versions</p>
                <p class="text-xl font-black text-slate-800 leading-none mt-0.5">{{ $totalVersions }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-star text-blue-500 text-sm"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Actuelle</p>
                <p class="text-xl font-black text-slate-800 leading-none mt-0.5">v{{ $currentVersion }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3.5 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-calendar-check text-emerald-500 text-sm"></i>
            </div>
            <div>
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Dernière màj</p>
                <p class="text-sm font-black text-slate-800 leading-none mt-0.5">
                    {{ $versions->first()?->created_at->format('d/m/Y') ?? '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Timeline</h2>
            <span class="text-[10px] font-bold text-slate-400">{{ $versions->firstItem() }}–{{ $versions->lastItem() }} sur {{ $totalVersions }}</span>
        </div>

        <div class="px-6 py-2">
            @foreach($versions as $i => $version)
            @php $isCurrent = $version->version_number == $currentVersion; @endphp

            <div class="relative flex gap-5 py-5 {{ !$loop->last ? 'border-b border-slate-50' : '' }}">

                {{-- Ligne verticale de timeline --}}
                @if(!$loop->last)
                <div class="absolute left-[19px] top-[52px] bottom-0 w-px bg-slate-100"></div>
                @endif

                {{-- Icône version --}}
                <div class="shrink-0 z-10">
                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-xs shadow-sm
                        {{ $isCurrent
                            ? 'bg-gradient-to-br from-orange-400 to-orange-600 text-white shadow-orange-200'
                            : 'bg-slate-100 text-slate-500' }}">
                        v{{ $version->version_number }}
                    </div>
                </div>

                {{-- Contenu --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">

                        <div class="flex-1 min-w-0">
                            {{-- Titre ligne --}}
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-sm font-black text-slate-800">Version {{ $version->version_number }}</span>
                                @if($isCurrent)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-orange-100 text-orange-600 rounded-lg text-[8px] font-black uppercase tracking-widest">
                                    <i class="fa-solid fa-circle text-[5px]"></i> Actuelle
                                </span>
                                @endif
                            </div>

                            {{-- Description --}}
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                {{ $version->change_description ?? 'Aucune description fournie.' }}
                            </p>

                            {{-- Meta --}}
                            <div class="flex items-center gap-3 mt-2 flex-wrap">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-5 h-5 rounded-md bg-slate-100 flex items-center justify-center text-[8px] font-black text-slate-500 shrink-0">
                                        {{ strtoupper(substr($version->creator->full_name ?? 'S', 0, 1)) }}
                                    </div>
                                    <span class="text-[10px] font-bold text-slate-600">{{ $version->creator->full_name ?? 'Système' }}</span>
                                </div>
                                <span class="text-slate-200 text-[10px]">·</span>
                                <span class="text-[10px] text-slate-400">
                                    <i class="fa-regular fa-calendar text-[8px] mr-1"></i>
                                    {{ $version->created_at->format('d/m/Y') }}
                                    <span class="text-slate-300 mx-1">à</span>
                                    {{ $version->created_at->format('H:i') }}
                                </span>
                                <span class="text-slate-200 text-[10px]">·</span>
                                <span class="text-[10px] text-slate-400">{{ $version->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- Checksum --}}
                            @if($version->checksum)
                            <div class="flex items-center gap-1.5 mt-2">
                                <i class="fa-solid fa-fingerprint text-[9px] text-slate-300"></i>
                                <span class="font-mono text-[9px] text-slate-300 tracking-wider">{{ substr($version->checksum, 0, 20) }}…</span>
                            </div>
                            @endif
                        </div>

                        {{-- Action --}}
                        <div class="shrink-0">
                            @if($isCurrent)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 text-orange-500 rounded-xl text-[9px] font-black uppercase">
                                <i class="fa-solid fa-circle-check text-[8px]"></i> En cours
                            </div>
                            @else
                            <form action="{{ route('documents.versions.restore', [$document, $version->version_number]) }}" method="POST"
                                  onsubmit="return confirm('Restaurer la version {{ $version->version_number }} comme version actuelle ?');">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-500 text-[9px] font-black uppercase px-3 py-1.5 rounded-xl border border-slate-100 hover:border-blue-200 transition-all">
                                    <i class="fa-solid fa-rotate-left text-[8px]"></i> Restaurer
                                </button>
                            </form>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($versions->hasPages())
        <div class="px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                Page {{ $versions->currentPage() }} sur {{ $versions->lastPage() }}
            </p>
            {{ $versions->links() }}
        </div>
        @endif
    </div>

    {{-- CTA upload si canEdit --}}
    @if($document->canEdit())
    <div class="bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-100 rounded-2xl px-6 py-5 flex flex-col sm:flex-row items-center gap-4">
        <div class="w-10 h-10 rounded-2xl bg-orange-100 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-upload text-orange-500 text-sm"></i>
        </div>
        <div class="flex-1 text-center sm:text-left">
            <p class="text-sm font-black text-slate-800">Vous avez modifié le document ?</p>
            <p class="text-xs text-slate-500 mt-0.5">Uploadez votre fichier Word pour créer la version v{{ $currentVersion + 1 }}.</p>
        </div>
        <button @click="uploadModal = true"
            class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all whitespace-nowrap">
            <i class="fa-solid fa-upload text-[10px]"></i> Uploader v{{ $currentVersion + 1 }}
        </button>
    </div>
    @endif

    {{-- ============================================================
         MODAL UPLOAD NOUVELLE VERSION
    ============================================================ --}}
    <div x-show="uploadModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="uploadModal = false"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Nouvelle version</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        v{{ $currentVersion }} → <span class="font-black text-orange-600">v{{ $currentVersion + 1 }}</span>
                    </p>
                </div>
                <button @click="uploadModal = false"
                    class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-400 transition-all">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <form action="{{ route('documents.upload-version', $document) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf

                <div
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="
                        dragging = false;
                        const f = $event.dataTransfer.files[0];
                        if (f && (f.name.endsWith('.docx') || f.name.endsWith('.doc'))) {
                            fileName = f.name;
                            const dt = new DataTransfer(); dt.items.add(f);
                            $refs.fileInput.files = dt.files;
                        }
                    "
                    :class="dragging ? 'border-orange-400 bg-orange-50' : (fileName ? 'border-emerald-300 bg-emerald-50/40' : 'border-slate-200 bg-slate-50 hover:border-orange-300 hover:bg-orange-50/30')"
                    class="relative border-2 border-dashed rounded-xl p-6 text-center transition-all cursor-pointer"
                    @click="$refs.fileInput.click()">

                    <input type="file" name="file" accept=".docx,.doc" required
                           x-ref="fileInput"
                           @change="const f = $event.target.files[0]; if(f) fileName = f.name;"
                           class="hidden">

                    <template x-if="!fileName">
                        <div>
                            <i class="fa-solid fa-file-word text-3xl text-slate-300 mb-2"></i>
                            <p class="text-xs font-bold text-slate-500">Glissez votre fichier Word ici</p>
                            <p class="text-[10px] text-slate-400 mt-1">ou cliquez pour parcourir</p>
                            <p class="text-[9px] text-slate-300 mt-2 font-mono">.docx / .doc — max 50 Mo</p>
                        </div>
                    </template>
                    <template x-if="fileName">
                        <div class="flex items-center gap-3 justify-center">
                            <i class="fa-solid fa-file-word text-2xl text-emerald-500"></i>
                            <div class="text-left">
                                <p class="text-xs font-bold text-slate-800" x-text="fileName"></p>
                                <p class="text-[10px] text-emerald-600 font-bold mt-0.5">Fichier sélectionné ✓</p>
                            </div>
                        </div>
                    </template>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                        Description des modifications <span class="text-slate-300 font-normal normal-case">(optionnel)</span>
                    </label>
                    <textarea name="change_description" rows="2"
                        placeholder="Ex: Correction section 3, mise à jour des annexes..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all resize-none"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="button" @click="uploadModal = false"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
                        Annuler
                    </button>
                    <button type="submit" :disabled="!fileName"
                        :class="fileName
                            ? 'bg-orange-600 hover:bg-orange-500 shadow-lg shadow-orange-200 text-white'
                            : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black transition-all">
                        <i class="fa-solid fa-upload text-[10px]"></i>
                        Uploader v{{ $currentVersion + 1 }}
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>{{-- fin x-data --}}

@endsection
