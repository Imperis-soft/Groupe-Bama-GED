@extends('layouts.app')

@section('content')
@php
    $docExt = $fileType->extension;
    // Mode effectif : les formats à convertir n'ont d'aperçu que si LibreOffice est disponible
    $mode = $fileType->previewMode === 'convert' ? ($canConvert ? 'convert-ready' : 'none') : $fileType->previewMode;
@endphp

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
                <div class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid {{ $fileType->icon() }} {{ $fileType->color() }} text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-black text-slate-800 truncate">{{ $document->title }}</p>
                    <p class="text-[9px] text-slate-400 font-mono">{{ $document->reference }} · v{{ $document->version }} · {{ strtoupper($docExt) }}</p>
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

        @switch($mode)

        @case('pdf')
        @case('convert-ready')
        {{-- PDF (natif ou converti côté serveur) --}}
        <div class="relative w-full" style="height: 85vh;" x-data="{ loading: true }">
            <div x-show="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-white">
                <div class="w-10 h-10 border-2 border-orange-200 border-t-orange-600 rounded-full animate-spin mb-4"></div>
                <p class="text-sm font-bold text-slate-400">Chargement de l'aperçu…</p>
                @if($mode === 'convert-ready')
                <p class="text-[10px] text-slate-300 mt-1">Conversion du fichier {{ $fileType->label() }} en PDF (la première ouverture peut prendre quelques secondes)</p>
                @endif
            </div>
            <iframe src="{{ $mode === 'pdf' ? route('documents.stream', $document) : route('documents.preview.pdf', $document) }}"
                    @load="loading = false"
                    class="w-full h-full border-0"
                    title="Prévisualisation - {{ $document->title }}"></iframe>
        </div>
        @break

        @case('image')
        <div class="bg-slate-100 flex items-center justify-center p-4 md:p-8 min-h-[60vh] overflow-auto" x-data="{ zoom: false }">
            <img src="{{ route('documents.stream', $document) }}" alt="{{ $document->title }}"
                 @click="zoom = !zoom"
                 :class="zoom ? 'max-w-none cursor-zoom-out' : 'max-w-full max-h-[80vh] cursor-zoom-in'"
                 class="rounded-lg shadow-md bg-white object-contain transition-all">
        </div>
        @break

        @case('sheet')
        <div x-data="sheetPreview(@js(route('documents.stream', $document)), @js($fileType->extension))" x-init="load()">
            <div x-show="state === 'loading'" class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-10 h-10 border-2 border-emerald-200 border-t-emerald-600 rounded-full animate-spin mb-4"></div>
                <p class="text-sm font-bold text-slate-400">Chargement du classeur…</p>
            </div>
            <div x-show="state === 'error'" x-cloak class="flex flex-col items-center justify-center py-16 text-center px-6">
                <i class="fa-solid fa-triangle-exclamation text-red-400 text-2xl mb-3"></i>
                <p class="text-sm font-black text-slate-700">Impossible d'afficher ce classeur</p>
                <p class="text-xs text-slate-400 mt-1" x-text="error"></p>
            </div>
            <div x-show="state === 'ready'" x-cloak>
                <div class="flex gap-1 px-3 pt-3 border-b border-slate-100 overflow-x-auto" x-show="sheets.length > 1">
                    <template x-for="(name, i) in sheets" :key="name">
                        <button @click="show(i)"
                                :class="i === current ? 'bg-white border-slate-200 text-emerald-700' : 'bg-slate-50 border-transparent text-slate-500 hover:text-slate-700'"
                                class="px-3 py-1.5 rounded-t-lg border border-b-0 text-xs font-bold whitespace-nowrap" x-text="name"></button>
                    </template>
                </div>
                <p x-show="truncated" class="px-4 py-2 text-[10px] font-bold text-amber-600 bg-amber-50 border-b border-amber-100">
                    Aperçu limité aux <span x-text="maxRows"></span> premières lignes. Téléchargez le fichier pour le consulter en entier.
                </p>
                <div class="overflow-auto" style="max-height: 78vh;">
                    <div id="sheetContent" class="sheet-preview"></div>
                </div>
            </div>
        </div>
        @break

        @case('text')
        <div x-data="{ state: 'loading', error: '' }"
             x-init="fetch(@js(route('documents.stream', $document)))
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
                .then(t => { $refs.text.textContent = t.length > 2000000 ? t.slice(0, 2000000) + '\n\n[…] Aperçu tronqué' : t; state = 'ready'; })
                .catch(e => { error = e.message; state = 'error'; })">
            <div x-show="state === 'loading'" class="py-20 text-center text-sm font-bold text-slate-400">Chargement…</div>
            <p x-show="state === 'error'" x-cloak class="py-16 text-center text-sm text-red-500" x-text="'Impossible de charger le fichier : ' + error"></p>
            <pre x-show="state === 'ready'" x-cloak x-ref="text"
                 class="px-6 md:px-10 py-6 text-xs leading-relaxed text-slate-700 font-mono whitespace-pre-wrap break-words overflow-auto" style="max-height: 80vh;"></pre>
        </div>
        @break

        @case('archive')
        <div x-data="{ state: 'loading', error: '', total: 0, entries: [] }"
             x-init="fetch(@js(route('documents.preview.archive', $document)), { headers: { 'Accept': 'application/json' } })
                .then(async r => { const d = await r.json(); if (!r.ok) throw new Error(d.message || ('HTTP ' + r.status)); return d; })
                .then(d => { total = d.total; entries = d.entries; state = 'ready'; })
                .catch(e => { error = e.message; state = 'error'; })">
            <div x-show="state === 'loading'" class="py-20 text-center text-sm font-bold text-slate-400">Lecture de l'archive…</div>
            <p x-show="state === 'error'" x-cloak class="py-16 text-center text-sm text-red-500" x-text="error"></p>
            <div x-show="state === 'ready'" x-cloak>
                <p class="px-5 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50">
                    <span x-text="total"></span> élément(s) dans l'archive
                    <span x-show="total > entries.length">— <span x-text="entries.length"></span> affichés</span>
                </p>
                <div class="divide-y divide-slate-50 max-h-[75vh] overflow-auto">
                    <template x-for="entry in entries" :key="entry.name">
                        <div class="flex items-center gap-3 px-5 py-2">
                            <i class="fa-solid text-xs" :class="entry.is_dir ? 'fa-folder text-amber-400' : 'fa-file text-slate-300'"></i>
                            <span class="flex-1 min-w-0 truncate text-xs font-medium text-slate-700" x-text="entry.name"></span>
                            <span class="text-[10px] text-slate-400 font-mono shrink-0" x-show="!entry.is_dir" x-text="formatBytes(entry.size)"></span>
                            <span class="text-[10px] text-slate-300 shrink-0 hidden sm:inline" x-text="entry.modified"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @break

        @case('docx')
        {{-- ============================================================
             WORD VIEWER (Mammoth.js)
        ============================================================ --}}

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
                    <span>{{ mb_strtoupper(brandName()) }} — Système de Gestion Documentaire</span>
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
        @break

        @default
        {{-- Aperçu impossible : format sans aperçu ou convertisseur absent --}}
        <div class="flex flex-col items-center justify-center py-16 text-center px-6">
            <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid {{ $fileType->icon() }} {{ $fileType->color() }} text-2xl"></i>
            </div>
            <p class="text-sm font-black text-slate-700">Aperçu non disponible pour ce fichier {{ $fileType->label() }}</p>
            <p class="text-xs text-slate-400 mt-1 max-w-md">
                @if($fileType->previewMode === 'convert')
                    L'aperçu des fichiers .{{ $fileType->extension }} nécessite le convertisseur LibreOffice sur le serveur.
                    @if(auth()->user()->hasRole('admin')) Contactez votre prestataire pour l'activer. @endif
                @else
                    Téléchargez le fichier pour l'ouvrir avec l'application adaptée.
                @endif
            </p>
            <a href="{{ route('documents.download', $document) }}"
               class="mt-5 inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
                <i class="fa-solid fa-download text-[10px]"></i> Télécharger
            </a>
        </div>
        @endswitch
    </div>

</div>

@if($mode === 'docx')
<script src="https://cdn.jsdelivr.net/npm/mammoth@1.4.21/mammoth.browser.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js"></script>
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

        // Nettoyage du HTML généré (liens javascript:, attributs dangereux…)
        const safeHtml = DOMPurify.sanitize(result.value || '');
        document.getElementById('previewContent').innerHTML = safeHtml || '<p class="text-slate-400 italic">Document vide.</p>';
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
@endif

@if($mode === 'sheet')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.7/dist/purify.min.js"></script>
<script>
function sheetPreview(url, extension) {
    return {
        state: 'loading', error: '', sheets: [], current: 0, truncated: false, maxRows: 1000, workbook: null,
        async load() {
            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const buf = await res.arrayBuffer();
                this.workbook = extension === 'csv'
                    ? XLSX.read(new TextDecoder('utf-8').decode(buf), { type: 'string', dense: true })
                    : XLSX.read(buf, { type: 'array', dense: true });
                this.sheets = this.workbook.SheetNames;
                this.state = 'ready';
                this.$nextTick(() => this.show(0));
            } catch (e) {
                this.error = e.message;
                this.state = 'error';
            }
        },
        show(index) {
            this.current = index;
            const sheet = this.workbook.Sheets[this.sheets[index]];
            this.truncated = false;
            if (sheet['!ref']) {
                const range = XLSX.utils.decode_range(sheet['!ref']);
                if (range.e.r - range.s.r + 1 > this.maxRows) {
                    range.e.r = range.s.r + this.maxRows - 1;
                    sheet['!ref'] = XLSX.utils.encode_range(range);
                    this.truncated = true;
                }
            }
            const html = sheet['!ref'] ? XLSX.utils.sheet_to_html(sheet, { header: '', footer: '' }) : '<p class="p-6 text-slate-400 italic">Feuille vide.</p>';
            document.getElementById('sheetContent').innerHTML = DOMPurify.sanitize(html);
        },
    };
}
</script>
<style>
.sheet-preview table { border-collapse: collapse; font-size: 12px; }
.sheet-preview td { border: 1px solid #e2e8f0; padding: 4px 8px; white-space: nowrap; color: #334155; }
.sheet-preview tr:first-child td { background: #f8fafc; font-weight: 700; position: sticky; top: 0; }
</style>
@endif

@if($mode === 'archive')
<script>
function formatBytes(bytes) {
    if (!bytes) return '0 o';
    const units = ['o', 'Ko', 'Mo', 'Go'];
    const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return (bytes / Math.pow(1024, i)).toFixed(i ? 1 : 0).replace('.', ',') + ' ' + units[i];
}
</script>
@endif
@endsection
