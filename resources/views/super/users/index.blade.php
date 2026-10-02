@extends('layouts.super')
@section('title', 'Utilisateurs')

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-user-group"></i> Administration</p>
        <h1 class="page-title">Utilisateurs</h1>
        <p class="page-subtitle">{{ $users->total() }} compte(s), toutes entreprises confondues.</p>
    </div>
</div>
@endsection

@section('content')

<form method="GET" class="filters">
    <div class="relative flex-1 min-w-[220px] max-w-sm">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-slate-400"></i>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom ou email" class="field !pl-10">
    </div>
    <select name="organization" class="field !w-auto" onchange="this.form.submit()">
        <option value="">Toutes les entreprises</option>
        @foreach($organizations as $org)
            <option value="{{ $org->id }}" @selected((string) request('organization') === (string) $org->id)>{{ $org->name }}</option>
        @endforeach
    </select>
    <select name="status" class="field !w-auto" onchange="this.form.submit()">
        <option value="">Tous les comptes</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Désactivés</option>
    </select>
    <button class="btn btn-light"><i class="fa-solid fa-sliders"></i> Filtrer</button>
    @if(request('q') || request('organization') || request('status'))
        <a href="{{ route('super.users.index') }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-xmark"></i> Réinitialiser</a>
    @endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data">
            <thead><tr><th>Utilisateur</th><th>Entreprise</th><th>Rôles</th><th>État</th><th class="!text-right">Actions</th></tr></thead>
            <tbody>
            @forelse($users as $user)
                <tr x-data="{ pwd: false }">
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="avatar {{ $user->isSuperAdmin() ? '!bg-slate-900 !text-white !ring-slate-900' : ($user->is_active ? '' : '!bg-slate-100 !text-slate-400 !ring-slate-200') }}">{{ initials($user->full_name) }}</span>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900">{{ $user->full_name }}</p>
                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($user->isSuperAdmin())
                            <span class="badge badge-dark"><i class="fa-solid fa-crown text-[9px] text-orange-400"></i> Super admin</span>
                        @elseif($user->organization)
                            <a href="{{ route('super.organizations.show', $user->organization) }}" class="text-slate-700 hover:text-orange-600">{{ $user->organization->name }}</a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            @foreach($user->roles as $role)
                                <span class="badge {{ $role->name === 'admin' ? 'badge-orange' : 'badge-slate' }}">{{ $role->display_name ?? $role->name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-green"><span class="dot"></span> Actif</span>
                        @else
                            <span class="badge badge-red"><span class="dot"></span> Désactivé</span>
                        @endif
                    </td>
                    <td class="text-right whitespace-nowrap">
                        @unless($user->isSuperAdmin())
                            <div class="inline-flex gap-1">
                                <button @click="pwd = !pwd" class="btn btn-ghost btn-sm" title="Changer le mot de passe"><i class="fa-solid fa-key"></i></button>
                                <form method="POST" action="{{ route('super.users.toggle-active', $user) }}">
                                    @csrf
                                    <button class="btn btn-ghost btn-sm {{ $user->is_active ? '!text-red-600 hover:!bg-red-50' : '!text-emerald-600 hover:!bg-emerald-50' }}">
                                        {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                </form>
                            </div>
                            <form x-show="pwd" x-cloak method="POST" action="{{ route('super.users.password', $user) }}" class="flex gap-2 mt-2 justify-end">
                                @csrf @method('PUT')
                                <input type="text" name="password" minlength="8" required placeholder="Nouveau mot de passe" class="field field-sm !w-48 font-mono" autocomplete="off">
                                <button class="btn btn-primary btn-sm">OK</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty">
                            <div class="empty-icon"><i class="fa-solid fa-user-group"></i></div>
                            <p class="text-sm font-bold text-slate-900">Aucun utilisateur trouvé</p>
                            <p class="text-sm text-slate-500 mt-1">Essayez avec d'autres critères de recherche.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $users->links() }}
@endsection
