@extends('layouts.app')

@section('content')
<div class="space-y-4">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors">Documents</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors truncate max-w-[140px]">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Prévisualisation</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Prévisualisation</h1>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('documents.download', $document) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-download text-[10px]"></i> Télécharger
            </a>
            <a href="{{ route('documents.show', $document) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
            </a>
        </div>
    </div>

    {{-- VIEWER --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- Doc info bar --}}
        <div class="flex items-center justify-between px-4 py-3 bg-slate-50 border-b border-slate-100">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-word text-orange-500 text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-black text-slate-800 truncate">{{ $document->title }}</p>
                    <p class="text-[9px] text-slate-400 font-mono">{{ $document->reference }} · v{{ $document->version }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($document->is_confidential)
                <span class="px-2 py-0.5 bg-red-50 text-red-600 border border-red-100 rounded-lg text-[9px] font-black uppercase">
                    <i class="fa-solid fa-lock mr-1"></i>Confidentiel
                </span>
                @endif
                <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold uppercase">
                    <i class="fa-solid fa-eye mr-1"></i>Lecture seule
                </span>
            </div>
        </div>

        {{-- Loading --}}
        <div id="loadingState" class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-10 h-10 border-2 border-orange-200 border-t-orange-600 rounded-full animate-spin mb-4"></div>
            <p class="text-sm font-bold text-slate-400">Chargement du document...</p>
            <p class="text-[10px] text-slate-300 mt-1">Conversion DOCX → HTML en cours</p>
        </div>

        {{-- Contenu --}}
        <div id="previewWrapper" class="hidden">
            <div class="bg-orange-50 border-b border-orange-100 px-6 md:px-12 py-3">
                <div class="flex items-center justify-between text-[10px] text-orange-700 font-bold">
                    <span>GROUPE BAMA — Système de Gestion Documentaire</span>
                    <span class="hidden sm:inline font-mono">{{ $document->reference }} | v{{ $document->version }}</span>
                </div>
                @if($document->is_confidential)
                <p class="text-center text-[10px] font-black text-red-600 mt-1 uppercase tracking-widest">⚠ Confidentiel — Accès restreint</p>
                @endif
            </div>
            <div id="previewContent"
                 class="px-6 md:px-16 py-8 min-h-[500px]"
                 style="font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.7; color: #1a1a1a;">
            </div>
        </div>

        {{-- Erreur --}}
        <div id="errorState" class="hidden flex flex-col items-center justify-center py-16 text-center px-6">
            <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
            </div>
            <p class="text-sm font-black text-slate-700 mb-1">Impossible de charger le document</p>
            <p id="errorMsg" class="text-xs text-slate-400 mb-4 max-w-sm"></p>
            <button onclick="loadPreview()"
                class="inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
                <i class="fa-solid fa-rotate-right text-[10px]"></i> Réessayer
            </button>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/mammoth@1.4.21/mammoth.browser.min.js"></script>
<script>
const DOC_URL = '{{ route('documents.stream', $document) }}';

async function loadPreview() {
    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('previewWrapper').classList.add('hidden');
    document.getElementById('errorState').classList.add('hidden');

    try {
        const res = await fetch(DOC_URL);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const buf    = await res.arrayBuffer();
        const result = await mammoth.convertToHtml({ arrayBuffer: buf });

        document.getElementById('previewContent').innerHTML = result.value || '<p class="text-slate-400 italic">Document vide.</p>';
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('previewWrapper').classList.remove('hidden');
    } catch (err) {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('errorMsg').textContent = err.message;
    }
}

loadPreview();
</script>

<style>
#previewContent h1 { font-size: 1.8em; font-weight: 800; margin-bottom: .5em; color: #0f172a; }
#previewContent h2 { font-size: 1.4em; font-weight: 700; margin-bottom: .5em; color: #1e293b; }
#previewContent h3 { font-size: 1.15em; font-weight: 700; margin-bottom: .4em; color: #334155; }
#previewContent p  { margin-bottom: .75em; }
#previewContent ul { list-style: disc; padding-left: 1.5em; margin-bottom: .75em; }
#previewContent ol { list-style: decimal; padding-left: 1.5em; margin-bottom: .75em; }
#previewContent blockquote { border-left: 3px solid #e2e8f0; padding-left: 1em; color: #64748b; font-style: italic; margin: 1em 0; }
#previewContent table { border-collapse: collapse; width: 100%; margin-bottom: 1em; }
#previewContent td, #previewContent th { border: 1px solid #e2e8f0; padding: .5em .75em; font-size: .9em; }
#previewContent th { background: #f8fafc; font-weight: 700; }
</style>
@endsection
