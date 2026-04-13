@extends('layouts.app')

@section('content')
<div class="space-y-4" x-data="{ fullscreen: false }">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium min-w-0">
            <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors shrink-0">Documents</a>
            <i class="fa-solid fa-chevron-right text-[8px] shrink-0"></i>
            <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors truncate max-w-[140px]">{{ $document->title }}</a>
            <i class="fa-solid fa-chevron-right text-[8px] shrink-0"></i>
            <span class="text-slate-600 font-bold shrink-0">Édition en ligne</span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span id="autosave-indicator" class="hidden items-center gap-1.5 text-[10px] font-bold text-slate-400">
                <span class="w-1.5 h-1.5 bg-amber-400 rounded-full animate-pulse inline-block"></span>
                Sauvegarde...
            </span>
            <span id="saved-indicator" class="hidden items-center gap-1.5 text-[10px] font-bold text-green-600">
                <i class="fa-solid fa-check text-[9px]"></i> Sauvegardé
            </span>
            <a href="{{ route('documents.show', $document) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-3 py-2 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span class="hidden sm:inline">Retour</span>
            </a>
            <button id="saveBtn"
                class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg shadow-orange-200 transition-all">
                <i class="fa-solid fa-floppy-disk text-[10px]"></i>
                <span>Sauvegarder</span>
            </button>
        </div>
    </div>

    {{-- EDITOR CARD --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden"
         :class="fullscreen ? 'fixed inset-4 z-50 flex flex-col' : ''">

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
                <button @click="fullscreen = !fullscreen"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-all">
                    <i class="fa-solid text-xs" :class="fullscreen ? 'fa-compress' : 'fa-expand'"></i>
                </button>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="border-b border-slate-100 bg-white px-3 py-2 overflow-x-auto" id="toolbar">
            <div class="flex items-center gap-0.5 min-w-max">
                <button data-action="bold"        title="Gras"       class="tbtn"><i class="fa-solid fa-bold"></i></button>
                <button data-action="italic"      title="Italique"   class="tbtn"><i class="fa-solid fa-italic"></i></button>
                <button data-action="underline"   title="Souligné"   class="tbtn"><i class="fa-solid fa-underline"></i></button>
                <button data-action="strike"      title="Barré"      class="tbtn"><i class="fa-solid fa-strikethrough"></i></button>
                <div class="w-px h-5 bg-slate-200 mx-1.5 shrink-0"></div>
                <button data-action="h1"          title="Titre 1"    class="tbtn font-black text-[10px]">H1</button>
                <button data-action="h2"          title="Titre 2"    class="tbtn font-black text-[10px]">H2</button>
                <button data-action="h3"          title="Titre 3"    class="tbtn font-black text-[10px]">H3</button>
                <button data-action="paragraph"   title="Paragraphe" class="tbtn text-[11px]">¶</button>
                <div class="w-px h-5 bg-slate-200 mx-1.5 shrink-0"></div>
                <button data-action="left"        title="Gauche"     class="tbtn"><i class="fa-solid fa-align-left"></i></button>
                <button data-action="center"      title="Centre"     class="tbtn"><i class="fa-solid fa-align-center"></i></button>
                <button data-action="right"       title="Droite"     class="tbtn"><i class="fa-solid fa-align-right"></i></button>
                <button data-action="justify"     title="Justifier"  class="tbtn"><i class="fa-solid fa-align-justify"></i></button>
                <div class="w-px h-5 bg-slate-200 mx-1.5 shrink-0"></div>
                <button data-action="bulletList"  title="Puces"      class="tbtn"><i class="fa-solid fa-list-ul"></i></button>
                <button data-action="orderedList" title="Numérotée"  class="tbtn"><i class="fa-solid fa-list-ol"></i></button>
                <div class="w-px h-5 bg-slate-200 mx-1.5 shrink-0"></div>
                <button data-action="blockquote"  title="Citation"   class="tbtn"><i class="fa-solid fa-quote-right"></i></button>
                <button data-action="code"        title="Code"       class="tbtn"><i class="fa-solid fa-code"></i></button>
                <div class="w-px h-5 bg-slate-200 mx-1.5 shrink-0"></div>
                <button data-action="undo"        title="Annuler"    class="tbtn"><i class="fa-solid fa-rotate-left"></i></button>
                <button data-action="redo"        title="Rétablir"   class="tbtn"><i class="fa-solid fa-rotate-right"></i></button>
                <div class="w-px h-5 bg-slate-200 mx-1.5 shrink-0"></div>
                <select id="fontSize" class="text-[10px] font-bold border border-slate-200 rounded-lg px-2 py-1 bg-white text-slate-600 focus:outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer">
                    <option value="">Taille</option>
                    <option value="10">10pt</option><option value="11">11pt</option>
                    <option value="12">12pt</option><option value="14">14pt</option>
                    <option value="16">16pt</option><option value="18">18pt</option>
                    <option value="24">24pt</option><option value="36">36pt</option>
                </select>
                <div class="flex items-center gap-1 ml-1">
                    <span class="text-[9px] text-slate-400 font-bold">A</span>
                    <input type="color" id="textColor" value="#000000"
                        class="w-6 h-6 rounded cursor-pointer border border-slate-200 p-0.5 bg-white">
                </div>
            </div>
        </div>

        {{-- Loading --}}
        <div id="loadingState" class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-10 h-10 border-2 border-orange-200 border-t-orange-600 rounded-full animate-spin mb-4"></div>
            <p class="text-sm font-bold text-slate-400">Chargement depuis MinIO...</p>
            <p class="text-[10px] text-slate-300 mt-1">Conversion DOCX → HTML en cours</p>
        </div>

        {{-- Editor wrapper --}}
        <div id="editorWrapper" class="hidden">
            <div class="bg-orange-50 border-b border-orange-100 px-6 md:px-12 py-3">
                <div class="flex items-center justify-between text-[10px] text-orange-700 font-bold">
                    <span>GROUPE BAMA — Système de Gestion Documentaire</span>
                    <span class="hidden sm:inline font-mono">{{ $document->reference }} | v{{ $document->version }}</span>
                </div>
                @if($document->is_confidential)
                <p class="text-center text-[10px] font-black text-red-600 mt-1 uppercase tracking-widest">⚠ Confidentiel — Accès restreint</p>
                @endif
            </div>
            <div id="editor" style="min-height:520px; padding: 2rem 4rem; font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.7;"></div>
        </div>

        {{-- Error --}}
        <div id="errorState" class="hidden flex flex-col items-center justify-center py-16 text-center px-6">
            <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
            </div>
            <p class="text-sm font-black text-slate-700 mb-1">Impossible de charger le document</p>
            <p id="errorMsg" class="text-xs text-slate-400 mb-4 max-w-sm"></p>
            <button onclick="loadDocument()"
                class="inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
                <i class="fa-solid fa-rotate-right text-[10px]"></i> Réessayer
            </button>
        </div>

    </div>
</div>

{{-- Toast --}}
<div id="toast" class="fixed bottom-5 right-5 z-[200] hidden">
    <div class="flex items-center gap-3 bg-slate-900 text-white px-4 py-3 rounded-2xl shadow-xl text-xs font-semibold">
        <div id="toastIconWrap" class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 bg-green-500/20">
            <i id="toastIcon" class="fa-solid fa-check text-green-400 text-[10px]"></i>
        </div>
        <span id="toastMsg"></span>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/mammoth@1.4.21/mammoth.browser.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
const SAVE_URL = '{{ route('documents.save-online', $document) }}';
const CSRF     = '{{ csrf_token() }}';
const DOC_URL  = '{{ route('documents.stream', $document) }}';

let quill        = null;
let isDirty      = false;
let autoSaveTimer = null;

function initEditor(html) {
    quill = new Quill('#editor', {
        theme: 'snow',
        modules: { toolbar: false, history: { delay: 1000, maxStack: 100 } },
        placeholder: 'Commencez à rédiger...',
    });

    if (html) {
        const delta = quill.clipboard.convert(html);
        quill.setContents(delta, 'silent');
    }

    quill.on('text-change', () => { isDirty = true; scheduleAutoSave(); updateToolbar(); });
    quill.on('selection-change', updateToolbar);

    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('editorWrapper').classList.remove('hidden');
}

async function loadDocument() {
    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('editorWrapper').classList.add('hidden');
    document.getElementById('errorState').classList.add('hidden');
    try {
        const res = await fetch(DOC_URL);
        if (!res.ok) throw new Error('HTTP ' + res.status + ' — vérifiez MinIO');
        const buf    = await res.arrayBuffer();
        const result = await mammoth.convertToHtml({ arrayBuffer: buf });
        initEditor(result.value);
    } catch (err) {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('errorMsg').textContent = err.message;
    }
}

async function saveDocument() {
    if (!quill) return;
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-[10px]"></i><span>Sauvegarde...</span>';
    try {
        const res = await fetch(SAVE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ content: quill.root.innerHTML }),
        });
        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.error || 'Erreur serveur');
        isDirty = false;
        showToast('Document sauvegardé', 'success');
        showSavedIndicator();
    } catch (err) {
        showToast('Erreur : ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk text-[10px]"></i><span>Sauvegarder</span>';
    }
}

function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        if (!isDirty) return;
        const ind = document.getElementById('autosave-indicator');
        ind.classList.remove('hidden'); ind.classList.add('flex');
        saveDocument().finally(() => { ind.classList.add('hidden'); ind.classList.remove('flex'); });
    }, 30000);
}

document.getElementById('toolbar').addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn || !quill) return;
    e.preventDefault();
    const a   = btn.dataset.action;
    const fmt = quill.getFormat();
    if      (a === 'bold')        quill.format('bold',       !fmt.bold);
    else if (a === 'italic')      quill.format('italic',     !fmt.italic);
    else if (a === 'underline')   quill.format('underline',  !fmt.underline);
    else if (a === 'strike')      quill.format('strike',     !fmt.strike);
    else if (a === 'h1')          quill.format('header', fmt.header === 1 ? false : 1);
    else if (a === 'h2')          quill.format('header', fmt.header === 2 ? false : 2);
    else if (a === 'h3')          quill.format('header', fmt.header === 3 ? false : 3);
    else if (a === 'paragraph')   quill.format('header', false);
    else if (a === 'left')        quill.format('align', false);
    else if (a === 'center')      quill.format('align', 'center');
    else if (a === 'right')       quill.format('align', 'right');
    else if (a === 'justify')     quill.format('align', 'justify');
    else if (a === 'bulletList')  quill.format('list', fmt.list === 'bullet'  ? false : 'bullet');
    else if (a === 'orderedList') quill.format('list', fmt.list === 'ordered' ? false : 'ordered');
    else if (a === 'blockquote')  quill.format('blockquote', !fmt.blockquote);
    else if (a === 'code')        quill.format('code',       !fmt.code);
    else if (a === 'undo')        quill.history.undo();
    else if (a === 'redo')        quill.history.redo();
    updateToolbar();
});

document.getElementById('fontSize').addEventListener('change', function () {
    if (!quill || !this.value) return;
    quill.format('size', this.value + 'px');
    this.value = '';
});

document.getElementById('textColor').addEventListener('input', function () {
    if (!quill) return;
    quill.format('color', this.value);
});

document.getElementById('saveBtn').addEventListener('click', saveDocument);

document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); saveDocument(); }
});

function updateToolbar() {
    if (!quill) return;
    const fmt = quill.getFormat();
    document.querySelectorAll('[data-action]').forEach(btn => {
        const a = btn.dataset.action;
        const on =
            a === 'bold'        ? !!fmt.bold :
            a === 'italic'      ? !!fmt.italic :
            a === 'underline'   ? !!fmt.underline :
            a === 'strike'      ? !!fmt.strike :
            a === 'h1'          ? fmt.header === 1 :
            a === 'h2'          ? fmt.header === 2 :
            a === 'h3'          ? fmt.header === 3 :
            a === 'left'        ? !fmt.align :
            a === 'center'      ? fmt.align === 'center' :
            a === 'right'       ? fmt.align === 'right' :
            a === 'justify'     ? fmt.align === 'justify' :
            a === 'bulletList'  ? fmt.list === 'bullet' :
            a === 'orderedList' ? fmt.list === 'ordered' :
            a === 'blockquote'  ? !!fmt.blockquote : false;
        btn.classList.toggle('tbtn-active', on);
    });
}

function showToast(msg, type) {
    const wrap = document.getElementById('toastIconWrap');
    const icon = document.getElementById('toastIcon');
    document.getElementById('toastMsg').textContent = msg;
    wrap.className = 'w-6 h-6 rounded-full flex items-center justify-center shrink-0 ' + (type === 'success' ? 'bg-green-500/20' : 'bg-red-500/20');
    icon.className = 'fa-solid text-[10px] ' + (type === 'success' ? 'fa-check text-green-400' : 'fa-xmark text-red-400');
    const t = document.getElementById('toast');
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 3500);
}

function showSavedIndicator() {
    const el = document.getElementById('saved-indicator');
    el.classList.remove('hidden'); el.classList.add('flex');
    setTimeout(() => { el.classList.add('hidden'); el.classList.remove('flex'); }, 3000);
}

window.addEventListener('beforeunload', e => { if (isDirty) { e.preventDefault(); e.returnValue = ''; } });

loadDocument();
</script>

<style>
.tbtn {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 28px; padding: 0 6px; border-radius: 8px;
    color: #64748b; font-size: 11px; border: none; background: transparent;
    cursor: pointer; transition: background .12s, color .12s; white-space: nowrap;
}
.tbtn:hover    { background: #f1f5f9; color: #0f172a; }
.tbtn-active   { background: #fff7ed !important; color: #ea580c !important; }

/* Quill overrides */
#editor .ql-container { border: none !important; }
#editor .ql-editor    { padding: 0; min-height: 480px; font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.7; color: #1a1a1a; }
#editor .ql-editor:focus { outline: none; }
#editor .ql-editor h1 { font-size: 1.8em; font-weight: 800; }
#editor .ql-editor h2 { font-size: 1.4em; font-weight: 700; }
#editor .ql-editor h3 { font-size: 1.15em; font-weight: 700; }
#editor .ql-editor blockquote { border-left: 3px solid #e2e8f0; padding-left: 1em; color: #64748b; font-style: italic; margin: 1em 0; }
#editor .ql-editor pre  { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: .75em 1em; font-family: monospace; }
#editor .ql-editor.ql-blank::before { color: #cbd5e1; font-style: normal; }
</style>
@endsection
