@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors">Documents</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors truncate max-w-[160px]">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Modifier</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Modifier le document</h1>
            <p class="text-xs text-slate-400 font-mono mt-1">{{ $document->reference }}</p>
        </div>
        <a href="{{ route('documents.show', $document) }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    <form action="{{ route('documents.update', $document) }}" method="POST" id="doc-edit-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

            {{-- Colonne principale (2/3) --}}
            <div class="xl:col-span-2 space-y-4">

                {{-- Informations générales --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-lines text-orange-500 text-xs"></i>
                        </div>
                        <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Informations générales</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                Titre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" required
                                   value="{{ old('title', $document->title) }}"
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all @error('title') border-red-300 @enderror">
                            @error('title')
                                <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Catégorie</label>
                                <select name="category_id"
                                        class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                                    <option value="">Sans catégorie</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id', $document->category_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Statut</label>
                                <select name="status"
                                        class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                                    <option value="draft"     {{ old('status', $document->status) == 'draft'     ? 'selected' : '' }}>Brouillon</option>
                                    <option value="review"    {{ old('status', $document->status) == 'review'    ? 'selected' : '' }}>En révision</option>
                                    <option value="approved"  {{ old('status', $document->status) == 'approved'  ? 'selected' : '' }}>Approuvé</option>
                                    <option value="archived"  {{ old('status', $document->status) == 'archived'  ? 'selected' : '' }}>Archivé</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Tags <span class="text-slate-300 font-normal normal-case">(séparés par des virgules)</span></label>
                            <input type="text" name="tags"
                                   value="{{ old('tags', is_array($document->tags) ? implode(', ', $document->tags) : ($document->tags ?? '')) }}"
                                   placeholder="contrat, finance, urgent..."
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Workflow d'approbation --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-list-check text-orange-500 text-xs"></i>
                        </div>
                        <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Workflow d'approbation</h2>
                    </div>

                    {{-- Hidden input that holds the JSON --}}
                    <input type="hidden" name="approval_workflow" id="approval_workflow_input">

                    <div id="workflow-steps" class="space-y-2 mb-3"></div>

                    <button type="button" id="add-workflow-step"
                        class="inline-flex items-center gap-2 text-[10px] font-bold text-orange-600 hover:text-orange-700 bg-orange-50 hover:bg-orange-100 border border-orange-200 px-3 py-2 rounded-lg transition-all">
                        <i class="fa-solid fa-plus text-[9px]"></i> Ajouter une étape
                    </button>
                    <p class="text-[9px] text-slate-400 mt-2">Ex : Vérification RH, Validation manager, Signature DG…</p>
                </div>

                {{-- Métadonnées --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">
                    <div class="flex items-center gap-2 mb-5">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-tags text-slate-500 text-xs"></i>
                        </div>
                        <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Métadonnées</h2>
                    </div>

                    {{-- Hidden input that holds the JSON --}}
                    <input type="hidden" name="metadata" id="metadata_input">

                    <div id="metadata-rows" class="space-y-2 mb-3"></div>

                    <button type="button" id="add-metadata-row"
                        class="inline-flex items-center gap-2 text-[10px] font-bold text-slate-600 hover:text-slate-800 bg-slate-50 hover:bg-slate-100 border border-slate-200 px-3 py-2 rounded-lg transition-all">
                        <i class="fa-solid fa-plus text-[9px]"></i> Ajouter un champ
                    </button>
                    <p class="text-[9px] text-slate-400 mt-2">Ex : projet → Refonte SI, département → DSI…</p>
                </div>

            </div>

            {{-- Colonne droite (1/3) --}}
            <div class="space-y-4">

                {{-- Sécurité --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-shield-halved text-red-400 text-xs"></i>
                        </div>
                        <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Sécurité</h2>
                    </div>

                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-red-50 hover:border-red-100 transition-all cursor-pointer group">
                        <input type="checkbox" name="is_confidential" value="1" id="is_confidential"
                               {{ old('is_confidential', $document->is_confidential) ? 'checked' : '' }}
                               class="w-4 h-4 text-red-500 border-slate-300 rounded focus:ring-red-400">
                        <div>
                            <p class="text-xs font-bold text-slate-700 group-hover:text-red-700 transition-colors">
                                <i class="fa-solid fa-lock text-red-400 mr-1.5"></i>Confidentiel
                            </p>
                            <p class="text-[9px] text-slate-400 mt-0.5">Accès restreint aux autorisés</p>
                        </div>
                    </label>
                </div>

                {{-- Rétention --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-clock text-blue-400 text-xs"></i>
                        </div>
                        <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Rétention</h2>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Durée de conservation</label>
                            <select name="retention_years"
                                    class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                                <option value="1"  {{ old('retention_years', $document->retention_years) == 1  ? 'selected' : '' }}>1 an</option>
                                <option value="3"  {{ old('retention_years', $document->retention_years) == 3  ? 'selected' : '' }}>3 ans</option>
                                <option value="5"  {{ old('retention_years', $document->retention_years) == 5  ? 'selected' : '' }}>5 ans</option>
                                <option value="10" {{ old('retention_years', $document->retention_years) == 10 ? 'selected' : '' }}>10 ans</option>
                                <option value="20" {{ old('retention_years', $document->retention_years) == 20 ? 'selected' : '' }}>20 ans</option>
                                <option value="0"  {{ old('retention_years', $document->retention_years) == 0  ? 'selected' : '' }}>Illimité</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Date d'expiration</label>
                            <input type="date" name="expires_at"
                                   value="{{ old('expires_at', $document->expires_at ? \Carbon\Carbon::parse($document->expires_at)->format('Y-m-d') : '') }}"
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-2">
                    <button type="submit"
                        class="flex items-center justify-center gap-2 w-full bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                        <i class="fa-solid fa-floppy-disk text-[10px]"></i> Enregistrer
                    </button>
                    <a href="{{ route('documents.show', $document) }}"
                       class="flex items-center justify-center gap-2 w-full bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                        Annuler
                    </a>
                </div>

                {{-- Info doc --}}
                <div class="bg-slate-50 rounded-2xl border border-slate-100 p-4 space-y-2">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Informations</p>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-medium">Référence</span>
                        <span class="font-mono font-bold text-slate-600">{{ $document->reference }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-medium">Version</span>
                        <span class="font-bold text-slate-600">{{ $document->version }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-medium">Créé le</span>
                        <span class="font-bold text-slate-600">{{ $document->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-slate-400 font-medium">Créateur</span>
                        <span class="font-bold text-slate-600 truncate max-w-[120px]">{{ $document->creator?->full_name ?? '—' }}</span>
                    </div>
                </div>

            </div>
        </div>
    </form>

</div>

<script>
// ─── Initial data from server ───────────────────────────────────────────────
const initialWorkflow = @json($document->approval_workflow ?? []);
const initialMetadata = @json($document->metadata ?? (object)[]);

// ─── Workflow steps ──────────────────────────────────────────────────────────
const workflowContainer = document.getElementById('workflow-steps');
const workflowInput     = document.getElementById('approval_workflow_input');

function renderWorkflowStep(value = '') {
    const idx = workflowContainer.children.length;
    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 group';
    row.innerHTML = `
        <span class="w-5 h-5 rounded-full bg-orange-100 text-orange-600 text-[9px] font-black flex items-center justify-center shrink-0 step-num">${idx + 1}</span>
        <input type="text" value="${escHtml(value)}" placeholder="Nom de l'étape…"
               class="flex-1 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all step-input">
        <button type="button"
                class="opacity-0 group-hover:opacity-100 w-6 h-6 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-400 hover:bg-red-50 transition-all remove-step">
            <i class="fa-solid fa-xmark text-[10px]"></i>
        </button>`;
    row.querySelector('.remove-step').addEventListener('click', () => {
        row.remove();
        renumberSteps();
        syncWorkflow();
    });
    row.querySelector('.step-input').addEventListener('input', syncWorkflow);
    workflowContainer.appendChild(row);
}

function renumberSteps() {
    workflowContainer.querySelectorAll('.step-num').forEach((el, i) => el.textContent = i + 1);
}

function syncWorkflow() {
    const steps = [...workflowContainer.querySelectorAll('.step-input')]
        .map(i => i.value.trim())
        .filter(v => v !== '');
    workflowInput.value = JSON.stringify(steps);
}

document.getElementById('add-workflow-step').addEventListener('click', () => {
    renderWorkflowStep();
    syncWorkflow();
});

// Init workflow
if (Array.isArray(initialWorkflow) && initialWorkflow.length > 0) {
    initialWorkflow.forEach(step => renderWorkflowStep(step));
}
syncWorkflow();

// ─── Metadata key/value rows ─────────────────────────────────────────────────
const metaContainer = document.getElementById('metadata-rows');
const metaInput     = document.getElementById('metadata_input');

function renderMetaRow(key = '', value = '') {
    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 group';
    row.innerHTML = `
        <input type="text" value="${escHtml(key)}" placeholder="Clé"
               class="w-2/5 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all meta-key">
        <span class="text-slate-300 text-xs font-bold shrink-0">→</span>
        <input type="text" value="${escHtml(value)}" placeholder="Valeur"
               class="flex-1 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-400 transition-all meta-val">
        <button type="button"
                class="opacity-0 group-hover:opacity-100 w-6 h-6 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-400 hover:bg-red-50 transition-all remove-meta">
            <i class="fa-solid fa-xmark text-[10px]"></i>
        </button>`;
    row.querySelector('.remove-meta').addEventListener('click', () => {
        row.remove();
        syncMeta();
    });
    row.querySelector('.meta-key').addEventListener('input', syncMeta);
    row.querySelector('.meta-val').addEventListener('input', syncMeta);
    metaContainer.appendChild(row);
}

function syncMeta() {
    const obj = {};
    metaContainer.querySelectorAll('.flex.items-center.gap-2.group').forEach(row => {
        const k = row.querySelector('.meta-key').value.trim();
        const v = row.querySelector('.meta-val').value.trim();
        if (k !== '') obj[k] = v;
    });
    metaInput.value = Object.keys(obj).length > 0 ? JSON.stringify(obj) : '';
}

document.getElementById('add-metadata-row').addEventListener('click', () => {
    renderMetaRow();
    syncMeta();
});

// Init metadata
if (initialMetadata && typeof initialMetadata === 'object' && !Array.isArray(initialMetadata)) {
    Object.entries(initialMetadata).forEach(([k, v]) => renderMetaRow(k, String(v)));
}
syncMeta();

// ─── Helpers ─────────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ─── Form submit: sync hidden inputs one last time ───────────────────────────
document.getElementById('doc-edit-form').addEventListener('submit', function() {
    syncWorkflow();
    syncMeta();
});
</script>
@endsection
