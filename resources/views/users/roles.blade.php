@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-black mb-4">Gérer les rôles — {{ $user->full_name }}</h1>

    <form action="{{ route('users.roles.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white p-6 rounded-2xl border mb-6">
            @foreach($roles as $role)
                <div class="flex items-center gap-3 mb-3">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" id="role-{{ $role->id }}" {{ $user->roles->contains($role) ? 'checked' : '' }}>
                    <label for="role-{{ $role->id }}" class="font-bold text-sm">{{ $role->display_name ?? $role->name }}</label>
                    <p class="text-xs text-slate-400">{{ $role->description }}</p>
                </div>
            @endforeach
        </div>

        <div class="flex gap-3">
            <a href="{{ route('users.index') }}" class="px-4 py-2 bg-slate-100 rounded font-bold">Annuler</a>
            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded font-bold">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
