@extends('layouts.app')

@section('title', 'Édition en ligne - ' . $document->title)

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('documents.show', $document) }}" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ $document->title }}</h1>
                        <p class="text-xs text-gray-500">Réf: {{ $document->reference }} &bull; Version: {{ $document->version }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <span id="autosave-indicator" class="hidden text-xs text-gray-500 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse inline-block"></span>
                        Sauvegarde auto...
                    </span>
                    <span id="saved-indicator" class="hidden text-xs text-green-600 font-medium">
                        <i class="fas fa-check mr-1"></i>Sauvegardé
                    </span>
                    <button id="saveBtn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i>Sauvegarder
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-1 py-2" id="toolbar">
                <!-- Text style -->
                <button data-action="bold" title="Gras (Ctrl+B)" class="toolbar-btn"><i class="fas fa-bold"></i></button>
                <button data-action="italic" title="Italique (Ctrl+I)" class="toolbar-btn"><i class="fas fa-italic"></i></button>
                <button data-action="underline" title="Souligné (Ctrl+U)" class="toolbar-btn"><i class="fas fa-underline"></i></button>
                <button data-action="strike" title="Barré" class="toolbar-btn"><i class="fas fa-strikethrough"></i></button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- Headings -->
                <button data-action="h1" title="Titre 1" class="toolbar-btn text-xs font-bold">H1</button>
                <button data-action="h2" title="Titre 2" class="toolbar-btn text-xs font-bold">H2</button>
                <button data-action="h3" title="Titre 3" class="toolbar-btn text-xs font-bold">H3</button>
                <button data-action="paragraph" title="Paragraphe" class="toolbar-btn text-xs">¶</button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- Alignment -->
                <button data-action="left" title="Aligner à gauche" class="toolbar-btn"><i class="fas fa-align-left"></i></button>
                <button data-action="center" title="Centrer" class="toolbar-btn"><i class="fas fa-align-center"></i></button>
                <button data-action="right" title="Aligner à droite" class="toolbar-btn"><i class="fas fa-align-right"></i></button>
                <button data-action="justify" title="Justifier" class="toolbar-btn"><i class="fas fa-align-justify"></i></button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- Lists -->
                <button data-action="bulletList" title="Liste à puces" class="toolbar-btn"><i class="fas fa-list-ul"></i></button>
                <button data-action="orderedList" title="Liste numérotée" class="toolbar-btn"><i class="fas fa-list-ol"></i></button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- Extras -->
                <button data-action="blockquote" title="Citation" class="toolbar-btn"><i class="fas fa-quote-right"></i></button>
                <button data-action="code" title="Code inline" class="toolbar-btn"><i class="fas fa-code"></i></button>
                <button data-action="hr" title="Séparateur" class="toolbar-btn text-xs">—</button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- History -->
                <button data-action="undo" title="Annuler (Ctrl+Z)" class="toolbar-btn"><i class="fas fa-undo"></i></button>
                <button data-action="redo" title="Rétablir (Ctrl+Y)" class="toolbar-btn"><i class="fas fa-redo"></i></button>

                <div class="w-px h-6 bg-gray-200 mx-1"></div>

                <!-- Font size -->
                <select id="fontSize" title="Taille de police" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">Taille</option>
                    <option value="10">10pt</option>
                    <option value="11">11pt</option>
                    <option value="12">12pt</option>
                    <option value="14">14pt</option>
                    <option value="16">16pt</option>
                    <option value="18">18pt</option>
                    <option value="24">24pt</option>
                    <option value="36">36pt</option>
                </select>

                <!-- Text color -->
                <input type="color" id="textColor" title="Couleur du texte" value="#000000"
                    class="w-7 h-7 rounded cursor-pointer border border-gray-200 p-0.5">
            </div>
        </div>
    </div>

    <!-- Editor area -->
    <div class="max-w-5xl mx-auto px-4 py-6">
        <!-- Loading state -->
        <div id="loadingState" class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-500 text-sm">Chargement du document...</p>
        </div>

        <!-- Editor -->
        <div id="editorWrapper" class="hidden bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Document header preview -->
            <div class="bg-orange-50 border-b border-orange-100 px-8 py-4">
                <div class="flex items-center justify-between text-xs text-orange-700">
                    <span class="font-bold">GROUPE BAMA — Système de Gestion Documentaire</span>
                    <span>Réf: {{ $document->reference }} | v{{ $document->version }}</span>
                </div>
                @if($document->is_confidential)
                <div class="mt-1 text-center text-xs font-bold text-red-600">CONFIDENTIEL — ACCÈS RESTREINT</div>
                @endif
            </div>

            <!-- TipTap editor mount point -->
            <div id="editor" class="min-h-[600px] px-12 py-8 focus:outline-none prose prose-sm max-w-none"></div>
        </div>

        <!-- Error state -->
        <div id="errorState" class="hidden bg-white rounded-xl shadow-sm border border-red-200 p-8 text-center">
            <i class="fas fa-exclamation-triangle text-red-400 text-3xl mb-3"></i>
            <p class="text-red-600 font-medium mb-2">Impossible de charger le document</p>
            <p id="errorMsg" class="text-gray-500 text-sm mb-4"></p>
            <button onclick="loadDocument()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">Réessayer</button>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="fixed bottom-6 right-6 z-50 hidden">
    <div class="bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg text-sm flex items-center gap-2">
        <i id="toastIcon" class="fas fa-check text-green-400"></i>
        <span id="toastMsg"></span>
    </div>
</div>
@endsection

@section('scripts')
<!-- Mammoth for DOCX → HTML -->
<script src="https://cdn.jsdelivr.net/npm/mammoth@1.4.21/mammoth.browser.min.js"></script>
<!-- TipTap via CDN (UMD build) -->
<script src="https://cdn.jsdelivr.net/npm/@tiptap/core@2.11.5/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/starter-kit@2.11.5/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-text-align@2.11.5/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-underline@2.11.5/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-color@2.11.5/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-text-style@2.11.5/dist/index.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tiptap/extension-font-size@2.11.5/dist/index.umd.min.js"></script>

<script>
const SAVE_URL = '{{ route('documents.save-online', $document) }}';
const CSRF    = '{{ csrf_token() }}';
const DOC_URL = '{{ route('documents.stream', $document) }}';

let editor = null;
let isDirty = false;
let autoSaveTimer = null;

// ── Init TipTap ──────────────────────────────────────────────────────────────
function initEditor(initialHtml) {
    const { Editor } = window['@tiptap/core'];
    const StarterKit = window['@tiptap/starter-kit'].StarterKit;
    const TextAlign  = window['@tiptap/extension-text-align'].TextAlign;
    const Underline  = window['@tiptap/extension-underline'].Underline;
    const Color      = window['@tiptap/extension-color'].Color;
    const TextStyle  = window['@tiptap/extension-text-style'].TextStyle;
    const FontSize   = window['@tiptap/extension-font-size']?.FontSize;

    const extensions = [
        StarterKit,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Underline,
        TextStyle,
        Color,
    ];
    if (FontSize) extensions.push(FontSize);

    editor = new Editor({
        element: document.getElementById('editor'),
        extensions,
        content: initialHtml || '<p>Commencez à rédiger votre document...</p>',
        onUpdate: () => {
            isDirty = true;
            scheduleAutoSave();
            updateToolbar();
        },
        onSelectionUpdate: updateToolbar,
    });

    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('editorWrapper').classList.remove('hidden');
    updateToolbar();
}

// ── Load document from MinIO ─────────────────────────────────────────────────
async function loadDocument() {
    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('editorWrapper').classList.add('hidden');
    document.getElementById('errorState').classList.add('hidden');

    try {
        const res = await fetch(DOC_URL);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const buf = await res.arrayBuffer();
        const result = await mammoth.convertToHtml({ arrayBuffer: buf });
        initEditor(result.value);
    } catch (err) {
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('errorState').classList.remove('hidden');
        document.getElementById('errorMsg').textContent = err.message;
    }
}

// ── Save ─────────────────────────────────────────────────────────────────────
async function saveDocument() {
    if (!editor) return;

    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sauvegarde...';

    try {
        const res = await fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ content: editor.getHTML() }),
        });

        const data = await res.json();
        if (!res.ok || data.error) throw new Error(data.error || 'Erreur serveur');

        isDirty = false;
        showToast('Document sauvegardé', 'success');
        showSavedIndicator();
    } catch (err) {
        showToast('Erreur: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Sauvegarder';
    }
}

// ── Auto-save ────────────────────────────────────────────────────────────────
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        if (isDirty) {
            document.getElementById('autosave-indicator').classList.remove('hidden');
            saveDocument().finally(() => {
                document.getElementById('autosave-indicator').classList.add('hidden');
            });
        }
    }, 30000);
}

// ── Toolbar actions ──────────────────────────────────────────────────────────
document.getElementById('toolbar').addEventListener('click', e => {
    const btn = e.target.closest('[data-action]');
    if (!btn || !editor) return;
    e.preventDefault();

    const action = btn.dataset.action;
    const chain = editor.chain().focus();

    switch (action) {
        case 'bold':        chain.toggleBold().run(); break;
        case 'italic':      chain.toggleItalic().run(); break;
        case 'underline':   chain.toggleUnderline().run(); break;
        case 'strike':      chain.toggleStrike().run(); break;
        case 'h1':          chain.toggleHeading({ level: 1 }).run(); break;
        case 'h2':          chain.toggleHeading({ level: 2 }).run(); break;
        case 'h3':          chain.toggleHeading({ level: 3 }).run(); break;
        case 'paragraph':   chain.setParagraph().run(); break;
        case 'left':        chain.setTextAlign('left').run(); break;
        case 'center':      chain.setTextAlign('center').run(); break;
        case 'right':       chain.setTextAlign('right').run(); break;
        case 'justify':     chain.setTextAlign('justify').run(); break;
        case 'bulletList':  chain.toggleBulletList().run(); break;
        case 'orderedList': chain.toggleOrderedList().run(); break;
        case 'blockquote':  chain.toggleBlockquote().run(); break;
        case 'code':        chain.toggleCode().run(); break;
        case 'hr':          chain.setHorizontalRule().run(); break;
        case 'undo':        chain.undo().run(); break;
        case 'redo':        chain.redo().run(); break;
    }
    updateToolbar();
});

// Font size
document.getElementById('fontSize').addEventListener('change', function () {
    if (!editor || !this.value) return;
    editor.chain().focus().setFontSize(this.value + 'pt').run();
    this.value = '';
});

// Text color
document.getElementById('textColor').addEventListener('input', function () {
    if (!editor) return;
    editor.chain().focus().setColor(this.value).run();
});

// Save button
document.getElementById('saveBtn').addEventListener('click', saveDocument);

// Keyboard shortcut Ctrl+S
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveDocument();
    }
});

// ── Toolbar state sync ───────────────────────────────────────────────────────
function updateToolbar() {
    if (!editor) return;
    const map = {
        bold: 'bold', italic: 'italic', underline: 'underline', strike: 'strike',
        bulletList: 'bulletList', orderedList: 'orderedList', blockquote: 'blockquote', code: 'code',
    };
    document.querySelectorAll('[data-action]').forEach(btn => {
        const action = btn.dataset.action;
        const isActive =
            map[action] ? editor.isActive(map[action]) :
            action === 'h1' ? editor.isActive('heading', { level: 1 }) :
            action === 'h2' ? editor.isActive('heading', { level: 2 }) :
            action === 'h3' ? editor.isActive('heading', { level: 3 }) :
            ['left','center','right','justify'].includes(action) ? editor.isActive({ textAlign: action }) :
            false;

        btn.classList.toggle('toolbar-active', isActive);
    });
}

// ── UI helpers ───────────────────────────────────────────────────────────────
function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    const icon  = document.getElementById('toastIcon');
    document.getElementById('toastMsg').textContent = msg;
    icon.className = type === 'success' ? 'fas fa-check text-green-400' : 'fas fa-times text-red-400';
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

function showSavedIndicator() {
    const el = document.getElementById('saved-indicator');
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 3000);
}

// Warn on unsaved changes
window.addEventListener('beforeunload', e => {
    if (isDirty) { e.preventDefault(); e.returnValue = ''; }
});

// Boot
loadDocument();
</script>

<style>
.toolbar-btn {
    padding: 0.375rem 0.625rem;
    border-radius: 0.375rem;
    color: #4b5563;
    font-size: 0.8rem;
    transition: background 0.15s, color 0.15s;
    border: none;
    background: transparent;
    cursor: pointer;
    line-height: 1;
}
.toolbar-btn:hover { background: #f3f4f6; color: #111827; }
.toolbar-btn.toolbar-active { background: #dbeafe; color: #1d4ed8; }

/* TipTap editor styles */
#editor { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.6; color: #1a1a1a; }
#editor:focus { outline: none; }
#editor .ProseMirror { min-height: 560px; outline: none; }
#editor .ProseMirror p { margin-bottom: 0.75em; }
#editor .ProseMirror h1 { font-size: 1.8em; font-weight: 700; margin-bottom: 0.5em; }
#editor .ProseMirror h2 { font-size: 1.4em; font-weight: 700; margin-bottom: 0.5em; }
#editor .ProseMirror h3 { font-size: 1.2em; font-weight: 600; margin-bottom: 0.5em; }
#editor .ProseMirror ul { list-style: disc; padding-left: 1.5em; margin-bottom: 0.75em; }
#editor .ProseMirror ol { list-style: decimal; padding-left: 1.5em; margin-bottom: 0.75em; }
#editor .ProseMirror blockquote { border-left: 3px solid #e5e7eb; padding-left: 1em; color: #6b7280; margin: 1em 0; }
#editor .ProseMirror code { background: #f3f4f6; padding: 0.1em 0.3em; border-radius: 3px; font-family: monospace; font-size: 0.9em; }
#editor .ProseMirror hr { border: none; border-top: 2px solid #e5e7eb; margin: 1.5em 0; }
#editor .ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
    float: left;
    height: 0;
}
</style>
@endsection
