@extends('layouts.super')
@section('title', 'Services')

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-sitemap"></i> Administration</p>
        <h1 class="page-title">Services</h1>
        <p class="page-subtitle">Liste commune à toutes les entreprises. Chaque administrateur y affecte ses utilisateurs et règle les accès de son entreprise.</p>
    </div>
</div>
@endsection

@section('content')
<div class="card overflow-hidden" x-data="{ adding: {{ $errors->has('name') && !old('_department') ? 'true' : 'false' }} }">
    <div class="card-header">
        <div>
            <h2 class="card-title">{{ $departments->count() }} service(s)</h2>
            <p class="card-subtitle">Un ajout ou un renommage s'applique immédiatement à toutes les entreprises.</p>
        </div>
        <button @click="adding = !adding" class="btn btn-sm" :class="adding ? 'btn-primary' : 'btn-light'"><i class="fa-solid fa-plus"></i> Nouveau service</button>
    </div>

    <form x-show="adding" x-cloak x-transition method="POST" action="{{ route('super.departments.store') }}"
          class="px-6 py-5 bg-slate-50/70 border-b border-slate-100 grid grid-cols-1 sm:grid-cols-[1fr_2fr_auto] gap-4 items-end">
        @csrf
        <div>
            <label class="label">Nom</label>
            <input type="text" name="name" value="{{ old('_department') ? '' : old('name') }}" required maxlength="255" placeholder="Ex. : Contrôle de gestion" class="field">
            @if(!old('_department')) @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
        </div>
        <div>
            <label class="label">Description</label>
            <input type="text" name="description" value="{{ old('_department') ? '' : old('description') }}" maxlength="2000" placeholder="Rôle du service (facultatif)" class="field">
        </div>
        <div class="flex gap-2">
            <button type="button" @click="adding = false" class="btn btn-ghost">Annuler</button>
            <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Créer</button>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data">
            <thead><tr><th>Service</th><th>Utilisation</th><th class="!text-right">Actions</th></tr></thead>
            <tbody>
            @forelse($departments as $department)
                @php
                    $usage = $members->get($department->id);
                    $editingThis = (int) old('_department') === $department->id;
                @endphp
                <tr x-data="{ editing: {{ $editingThis && $errors->has('name') ? 'true' : 'false' }}, removing: {{ $errors->has('merge_into') && $editingThis ? 'true' : 'false' }} }">
                    <td class="align-top">
                        <div x-show="!editing" class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-sitemap text-xs"></i></span>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900">{{ $department->name }}</p>
                                @if($department->description)<p class="text-xs text-slate-500 mt-0.5">{{ $department->description }}</p>@endif
                            </div>
                        </div>
                        <form x-show="editing" x-cloak method="POST" action="{{ route('super.departments.update', $department) }}" class="grid grid-cols-1 sm:grid-cols-[1fr_2fr_auto] gap-2 items-start">
                            @csrf @method('PUT')
                            <input type="hidden" name="_department" value="{{ $department->id }}">
                            <div>
                                <input type="text" name="name" value="{{ $editingThis ? old('name') : $department->name }}" required maxlength="255" class="field field-sm">
                                @if($editingThis) @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror @endif
                            </div>
                            <input type="text" name="description" value="{{ $editingThis ? old('description') : $department->description }}" maxlength="2000" placeholder="Description" class="field field-sm">
                            <div class="flex gap-1">
                                <button type="button" @click="editing = false" class="btn btn-ghost btn-sm">Annuler</button>
                                <button class="btn btn-primary btn-sm">OK</button>
                            </div>
                        </form>
                    </td>
                    <td class="align-top whitespace-nowrap text-sm text-slate-600">
                        @if($usage)
                            <span class="font-bold text-slate-900 tabular-nums">{{ $usage->members }}</span> membre(s)
                            <span class="text-slate-400">· {{ $usage->organizations }} entreprise(s)</span>
                        @else
                            <span class="text-slate-400">Aucun membre</span>
                        @endif
                    </td>
                    <td class="align-top text-right whitespace-nowrap">
                        <div class="inline-flex gap-1" x-show="!editing">
                            <button @click="editing = true; removing = false" class="btn btn-ghost btn-sm" title="Modifier"><i class="fa-solid fa-pen"></i></button>
                            <button @click="removing = !removing" class="btn btn-ghost btn-sm !text-red-600 hover:!bg-red-50" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                        </div>
                        <form x-show="removing" x-cloak method="POST" action="{{ route('super.departments.destroy', $department) }}"
                              class="mt-2 flex flex-col items-end gap-2 text-left"
                              onsubmit="return confirm('Supprimer le service « {{ addslashes($department->name) }} » dans toutes les entreprises ?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="_department" value="{{ $department->id }}">
                            @if($usage)
                                <label class="text-xs text-slate-600">Transférer ses {{ $usage->members }} membre(s) et ses accès vers :</label>
                                <select name="merge_into" required class="field field-sm !w-56">
                                    <option value="">Choisir un service…</option>
                                    @foreach($departments->where('id', '!=', $department->id) as $other)
                                        <option value="{{ $other->id }}">{{ $other->name }}</option>
                                    @endforeach
                                </select>
                                @if($editingThis) @error('merge_into') <p class="text-xs text-red-600">{{ $message }}</p> @enderror @endif
                            @endif
                            <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Supprimer le service</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        <div class="empty">
                            <div class="empty-icon"><i class="fa-solid fa-sitemap"></i></div>
                            <p class="font-bold text-slate-900">Aucun service</p>
                            <p class="text-sm text-slate-500 mt-1">Créez les services communs, ou lancez <span class="font-mono">php artisan db:seed --class=DepartmentSeeder</span>.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
