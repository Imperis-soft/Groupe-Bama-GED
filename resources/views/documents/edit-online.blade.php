@extends('layouts.app')

@section('content')
<div class="space-y-4">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold min-w-0 overflow-hidden">
            <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors shrink-0">Documents</a>
            <i class="fa-solid fa-chevron-right text-[8px] shrink-0"></i>
            <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors truncate">{{ $document->title }}</a>
            <i class="fa-solid fa-chevron-right text-[8px] shrink-0"></i>
            <span class="text-slate-600 font-bold shrink-0">Édition en ligne</span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            {{-- Indicateur collaboratif --}}
            <div class="hidden sm:flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-black px-3 py-1.5 rounded-xl">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                OnlyOffice actif
            </div>
            <a href="{{ route('documents.show', $document) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-3 py-2 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span class="hidden sm:inline">Retour</span>
            </a>
        </div>
    </div>

    {{-- DOC INFO BAR --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-file-word text-orange-500 text-sm"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-black text-slate-800 truncate">{{ $document->title }}</p>
                <p class="text-[9px] text-slate-400 font-mono mt-0.5">{{ $document->reference }} · v{{ $document->version }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            @if($document->is_confidential)
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-50 text-red-600 border border-red-100 rounded-lg text-[9px] font-black uppercase">
                <i class="fa-solid fa-lock text-[8px]"></i> Confidentiel
            </span>
            @endif
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded-lg text-[9px] font-black uppercase">
                <i class="fa-solid fa-users text-[8px]"></i> Collaboratif
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-lg text-[9px] font-black uppercase">
                <i class="fa-solid fa-floppy-disk text-[8px]"></i> Sauvegarde auto
            </span>
        </div>
    </div>

    {{-- ONLYOFFICE IFRAME --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden" style="height: calc(100vh - 220px); min-height: 500px;">
        <iframe
            id="onlyoffice-frame"
            src="{{ $editorData['editUrl'] }}"
            width="100%"
            height="100%"
            frameborder="0"
            allowfullscreen
            style="display:block;"
        ></iframe>
    </div>

    {{-- INFO FOOTER --}}
    <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 text-[10px] text-slate-400 font-medium flex-wrap">
        <i class="fa-solid fa-circle-info text-blue-400 shrink-0"></i>
        <span>Les modifications sont <strong class="text-slate-600">sauvegardées automatiquement</strong> sur le serveur.</span>
        <span class="hidden sm:inline text-slate-200">|</span>
        <span class="hidden sm:inline">Édition collaborative en temps réel activée.</span>
        <a href="{{ route('documents.show', $document) }}"
           class="ml-auto inline-flex items-center gap-1.5 text-orange-600 hover:text-orange-700 font-bold transition-colors shrink-0">
            <i class="fa-solid fa-arrow-left text-[9px]"></i> Retour au document
        </a>
    </div>

</div>
@endsection
