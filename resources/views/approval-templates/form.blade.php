@extends('layouts.app')

@section('content')
@php
    $isEdit = $template->exists;
    $steps = old('steps', $template->steps ?? []);
    $inputClass = 'w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2.5 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500';
@endphp
<div class="space-y-5 max-w-3xl">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
            <a href="{{ route('approval-templates.index') }}" class="hover:text-orange-600">Circuits d'approbation</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <span class="text-slate-600 font-bold">{{ $isEdit ? 'Modifier' : 'Nouveau' }}</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $isEdit ? $template->name : 'Nouveau circuit' }}</h1>
    </div>

    <form method="POST" action="{{ $isEdit ? route('approval-templates.update', $template) : route('approval-templates.store') }}"
          class="space-y-5"
          x-data="templateSteps(@js(array_values($steps)))">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="name" required maxlength="80" value="{{ old('name', $template->name) }}" placeholder="Ex : Validation des factures" class="{{ $inputClass }}">
                @error('name')<p class="text-red-500 text-[10px] font-bold mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Proposé pour la catégorie</label>
                <select name="category_id" class="{{ $inputClass }}">
                    <option value="">Aucune (toujours disponible)</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (int) old('category_id', $template->category_id) === $category->id ? 'selected' : '' }}>{{ str_repeat('— ', $category->depth ?? 0) }}{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Mis en avant pour les documents de cette catégorie et de ses sous-catégories.</p>
            </div>
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Description</label>
                <input type="text" name="description" maxlength="500" value="{{ old('description', $template->description) }}" placeholder="Quand utiliser ce circuit ?" class="{{ $inputClass }}">
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Étapes, dans l'ordre</h2>
                <span class="text-[10px] text-slate-400">Le délai court à partir du moment où l'étape devient active</span>
            </div>
            @error('steps')<p class="text-red-500 text-[10px] font-bold mb-2">{{ $message }}</p>@enderror
            @foreach($errors->get('steps.*') as $messages)
            <p class="text-red-500 text-[10px] font-bold mb-2">{{ $messages[0] }}</p>
            @endforeach

            <div class="space-y-2">
                <template x-for="(step, index) in steps" :key="step.key">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 bg-slate-50 border border-slate-100 rounded-xl p-3">
                        <span class="w-6 h-6 rounded-lg bg-orange-500 text-white text-[10px] font-black flex items-center justify-center shrink-0" x-text="index + 1"></span>
                        <select :name="'steps[' + index + '][type]'" x-model="step.type" class="{{ $inputClass }} sm:w-56 bg-white">
                            @foreach(\App\Models\ApprovalTemplate::STEP_TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select x-show="step.type === 'user'" :name="'steps[' + index + '][user_id]'" x-model="step.user_id" class="{{ $inputClass }} flex-1 bg-white">
                            <option value="">Choisir une personne…</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                            @endforeach
                        </select>
                        <select x-show="step.type === 'department_manager'" :name="'steps[' + index + '][department_id]'" x-model="step.department_id" class="{{ $inputClass }} flex-1 bg-white">
                            <option value="">Choisir un service…</option>
                            @foreach($departments as $department)
                            <option value="{{ $department['id'] }}">{{ $department['name'] }}{{ $department['manager'] ? ' — ' . $department['manager'] : ' — responsable non défini' }}</option>
                            @endforeach
                        </select>
                        <p x-show="step.type === 'creator_manager'" class="flex-1 text-[11px] text-slate-500 px-1">Retrouvé selon le service de la personne qui lance la validation.</p>
                        <div class="relative sm:w-28 shrink-0">
                            <input type="number" min="1" max="365" :name="'steps[' + index + '][due_days]'" x-model="step.due_days" placeholder="Délai"
                                   class="{{ $inputClass }} bg-white pr-8">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">j</span>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" @click="move(index, -1)" :disabled="index === 0" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white disabled:opacity-30"><i class="fa-solid fa-chevron-up text-[9px]"></i></button>
                            <button type="button" @click="move(index, 1)" :disabled="index === steps.length - 1" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white disabled:opacity-30"><i class="fa-solid fa-chevron-down text-[9px]"></i></button>
                            <button type="button" @click="steps.splice(index, 1)" :disabled="steps.length === 1" class="w-7 h-7 flex items-center justify-center rounded-lg text-red-300 hover:bg-red-50 hover:text-red-500 disabled:opacity-30"><i class="fa-solid fa-xmark text-[10px]"></i></button>
                        </div>
                    </div>
                </template>
            </div>
            <button type="button" @click="add()" x-show="steps.length < {{ \App\Models\ApprovalTemplate::MAX_STEPS }}"
                    class="mt-3 inline-flex items-center gap-2 text-xs font-bold text-orange-600 hover:text-orange-700">
                <i class="fa-solid fa-plus text-[10px]"></i> Ajouter une étape
            </button>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('approval-templates.index') }}" class="flex-1 sm:flex-none sm:px-8 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase tracking-wider">Annuler</a>
            <button type="submit" class="flex-1 sm:flex-none sm:px-8 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> {{ $isEdit ? 'Enregistrer' : 'Créer le circuit' }}
            </button>
        </div>
    </form>
</div>

<script>
function templateSteps(initial) {
    let key = 0;
    const normalize = s => ({ key: ++key, type: s.type || 'user', user_id: String(s.user_id ?? ''), department_id: String(s.department_id ?? ''), due_days: s.due_days ?? '' });
    return {
        steps: (initial.length ? initial : [{ type: 'creator_manager', due_days: 3 }]).map(normalize),
        add() { this.steps.push(normalize({ type: 'user' })); },
        move(index, delta) {
            const target = index + delta;
            if (target < 0 || target >= this.steps.length) return;
            [this.steps[index], this.steps[target]] = [this.steps[target], this.steps[index]];
            this.steps = [...this.steps];
        },
    };
}
</script>
@endsection
