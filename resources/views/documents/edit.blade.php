@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Éditer le document</h1>
            <p class="text-xs text-slate-500 font-medium">Référence: {{ $document->reference }}</p>
        </div>
        <a href="{{ route('documents.index') }}" class="text-slate-500 hover:text-slate-700 font-bold text-xs flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>

    <form action="{{ route('documents.update', $document) }}" method="POST" id="doc-edit-form" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        @csrf
        @method('PUT')

        <div class="p-8 space-y-8">
            <!-- Informations générales -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider border-b border-slate-100 pb-2">Informations générales</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Titre</label>
                        <input type="text" name="title" value="{{ old('title', $document->title) }}" required class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-orange-500 outline-none">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Catégorie</label>
                        <select name="category_id" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold outline-none">
                            <option value="">Aucune</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $document->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Statut</label>
                        <select name="status" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold outline-none">
                            <option value="draft" {{ old('status', $document->status) == 'draft' ? 'selected' : '' }}>Brouillon</option>
                            <option value="review" {{ old('status', $document->status) == 'review' ? 'selected' : '' }}>En révision</option>
                            <option value="approved" {{ old('status', $document->status) == 'approved' ? 'selected' : '' }}>Approuvé</option>
                            <option value="archived" {{ old('status', $document->status) == 'archived' ? 'selected' : '' }}>Archivé</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Sécurité et Rétention -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider border-b border-slate-100 pb-2">Sécurité & Rétention</h3>

                <div class="flex items-center gap-3 mb-4">
                    <input type="checkbox" name="is_confidential" value="1" id="is_confidential" {{ old('is_confidential', $document->is_confidential) ? 'checked' : '' }} class="w-4 h-4 text-orange-600 bg-slate-50 border-slate-300 rounded focus:ring-orange-500">
                    <label for="is_confidential" class="text-sm font-bold text-slate-700">
                        <i class="fa-solid fa-lock mr-2 text-red-500"></i>
                        Document confidentiel
                    </label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Durée de rétention (années)</label>
                        <select name="retention_years" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold outline-none">
                            <option value="1" {{ old('retention_years', $document->retention_years) == 1 ? 'selected' : '' }}>1 an</option>
                            <option value="3" {{ old('retention_years', $document->retention_years) == 3 ? 'selected' : '' }}>3 ans</option>
                            <option value="5" {{ old('retention_years', $document->retention_years) == 5 ? 'selected' : '' }}>5 ans</option>
                            <option value="10" {{ old('retention_years', $document->retention_years) == 10 ? 'selected' : '' }}>10 ans</option>
                            <option value="20" {{ old('retention_years', $document->retention_years) == 20 ? 'selected' : '' }}>20 ans</option>
                            <option value="0" {{ old('retention_years', $document->retention_years) == 0 ? 'selected' : '' }}>Illimité</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Date d'expiration</label>
                        <input type="date" name="expires_at" value="{{ old('expires_at', $document->expires_at ? \Carbon\Carbon::parse($document->expires_at)->format('Y-m-d') : '') }}" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-orange-500 outline-none">
                    </div>
                </div>
            </div>

            <!-- Organisation et Avancé -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wider border-b border-slate-100 pb-2">Organisation & Avancé</h3>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Tags (séparés par des virgules)</label>
                    <input type="text" name="tags" value="{{ old('tags', is_array($document->tags) ? implode(',', $document->tags) : ($document->tags ?? '')) }}" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold outline-none">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Workflow d'approbation (JSON)</label>
                        <textarea name="approval_workflow" id="approval_workflow" rows="4" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-xs font-mono outline-none">{{ old('approval_workflow', json_encode($document->approval_workflow ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
                        <p id="workflow-error" class="text-red-600 text-xs mt-1 hidden font-bold">JSON invalide.</p>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Métadonnées (JSON)</label>
                        <textarea name="metadata" id="metadata" rows="4" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-xs font-mono outline-none">{{ old('metadata', json_encode($document->metadata ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
                        <p id="meta-error" class="text-red-600 text-xs mt-1 hidden font-bold">JSON invalide.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 px-8 py-4 flex items-center justify-end gap-3 border-t border-slate-100">
            <a href="{{ route('documents.index') }}" class="px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold text-xs uppercase hover:bg-slate-50 transition">Annuler</a>
            <button type="submit" class="px-6 py-3 bg-orange-600 text-white rounded-xl font-bold text-xs uppercase shadow-lg shadow-orange-100 hover:bg-orange-700 transition">Enregistrer les modifications</button>
        </div>
    </form>
</div>

<script>
document.getElementById('doc-edit-form').addEventListener('submit', function(e){
    let valid = true;

    // Validate Metadata JSON
    const meta = document.getElementById('metadata').value.trim();
    if (meta.length) {
        try {
            JSON.parse(meta);
            document.getElementById('meta-error').classList.add('hidden');
        } catch (err) {
            valid = false;
            document.getElementById('meta-error').classList.remove('hidden');
            document.getElementById('metadata').focus();
        }
    }

    // Validate Workflow JSON
    const workflow = document.getElementById('approval_workflow').value.trim();
    if (workflow.length) {
        try {
            JSON.parse(workflow);
            document.getElementById('workflow-error').classList.add('hidden');
        } catch (err) {
            valid = false;
            document.getElementById('workflow-error').classList.remove('hidden');
            if(valid) document.getElementById('approval_workflow').focus(); // Focus only if meta was valid
        }
    }

    if (!valid) {
        e.preventDefault();
    }
});
</script>
@endsection
