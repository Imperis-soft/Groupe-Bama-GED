@extends('layouts.super')
@section('title', $organization->name)

@php
    $maxUsers   = $plan?->max_users;
    $maxStorage = $plan?->max_storage_mb ? $plan->max_storage_mb * 1024 * 1024 : null;
    $pct = fn ($used, $max) => $max ? min(100, (int) round($used * 100 / $max)) : null;
@endphp

@section('header')
<a href="{{ route('super.organizations.index') }}" class="back-link"><i class="fa-solid fa-arrow-left text-xs"></i> Entreprises</a>

<div>
    <div class="flex flex-wrap items-center gap-6">
        <span class="avatar avatar-lg !w-20 !h-20 !text-2xl shadow-lift">{{ initials($organization->name) }}</span>
        <div class="min-w-[280px] flex-1">
            <p class="eyebrow mb-2"><i class="fa-solid fa-building"></i> Entreprise cliente</p>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="page-title">{{ $organization->name }}</h1>
                @include('super.organizations._status')
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-slate-500">
                <span><i class="fa-solid fa-hashtag w-4 text-slate-400"></i> <span class="font-mono font-medium text-slate-700">{{ $organization->reference_prefix }}</span></span>
                @if($organization->email)<span><i class="fa-regular fa-envelope w-4 text-slate-400"></i> {{ $organization->email }}</span>@endif
                @if($organization->phone)<span><i class="fa-solid fa-phone w-4 text-slate-400 text-[12px]"></i> {{ $organization->phone }}</span>@endif
                <span><i class="fa-regular fa-calendar w-4 text-slate-400"></i> Cliente depuis le {{ $organization->created_at->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('super.organizations.edit', $organization) }}" class="btn btn-light"><i class="fa-solid fa-pen"></i> Modifier</a>
            <a href="{{ route('super.organizations.settings', $organization) }}" class="btn btn-light"><i class="fa-solid fa-sliders"></i> Configuration</a>
            <a href="{{ route('super.subscriptions.create', $organization) }}" class="btn btn-light"><i class="fa-solid fa-rotate"></i> Renouveler</a>
            @if($organization->isSuspended())
                <form method="POST" action="{{ route('super.organizations.activate', $organization) }}">
                    @csrf
                    <button class="btn btn-light !text-emerald-700"><i class="fa-solid fa-circle-check"></i> Réactiver</button>
                </form>
            @else
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Suspendre</button>
                    <form x-show="open" x-cloak x-transition @click.outside="open = false" method="POST" action="{{ route('super.organizations.suspend', $organization) }}"
                          class="dropdown right-0 w-80 p-5 space-y-3">
                        @csrf
                        <div>
                            <p class="text-sm font-bold text-slate-900">Suspendre l'accès</p>
                            <p class="text-xs text-slate-500 mt-0.5">Les utilisateurs ne pourront plus se connecter.</p>
                        </div>
                        <div>
                            <label class="label">Motif (visible par le client)</label>
                            <input type="text" name="reason" required placeholder="Ex. : impayé" class="field">
                        </div>
                        <button class="btn btn-danger-solid w-full">Confirmer la suspension</button>
                    </form>
                </div>
            @endif
            <form method="POST" action="{{ route('super.organizations.enter', $organization) }}">
                @csrf
                <button class="btn btn-primary" title="Ouvrir l'espace de l'entreprise avec les droits d'administrateur (tracé)">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Entrer dans l'espace
                </button>
            </form>
        </div>
    </div>
    @if($organization->isSuspended())
        <div class="mt-5 flex gap-3 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-800">
            <i class="fa-solid fa-ban mt-0.5"></i>
            <p><span class="font-semibold">Entreprise suspendue :</span> {{ $organization->suspended_reason }}</p>
        </div>
    @endif
</div>

@endsection

@section('content')
{{-- Indicateurs --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">
    <div class="card p-6">
        <p class="text-[13px] font-semibold text-slate-500">Abonnement</p>
        @if($current)
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ $plan->name }}</p>
            <p class="mt-1 text-[13px] text-slate-500">jusqu'au {{ $current->ends_at->format('d/m/Y') }} · <span class="font-medium {{ $current->daysRemaining() <= 7 ? 'text-red-600' : 'text-slate-700' }}">{{ $current->daysRemaining() }} j</span></p>
        @else
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-red-600">Aucun en cours</p>
            <p class="mt-1 text-[13px] text-slate-500">Les utilisateurs n'ont pas accès</p>
        @endif
    </div>
    <div class="card p-6">
        <p class="text-[13px] font-semibold text-slate-500">Utilisateurs</p>
        <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ $stats['users'] }} <span class="text-sm font-medium text-slate-400">/ {{ $maxUsers ?? '∞' }}</span></p>
        @if($maxUsers)
            <div class="meter mt-3"><span class="{{ $pct($stats['users'], $maxUsers) >= 100 ? '!bg-none !bg-red-500' : '' }}" style="width: {{ $pct($stats['users'], $maxUsers) }}%"></span></div>
        @else
            <p class="mt-1 text-[13px] text-slate-500">Sans limite</p>
        @endif
    </div>
    <div class="card p-6">
        <p class="text-[13px] font-semibold text-slate-500">Stockage</p>
        <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ formatBytes($stats['storage_bytes']) }} <span class="text-sm font-medium text-slate-400">/ {{ $maxStorage ? formatBytes($maxStorage) : '∞' }}</span></p>
        @if($maxStorage)
            <div class="meter mt-3"><span class="{{ $pct($stats['storage_bytes'], $maxStorage) >= 90 ? '!bg-none !bg-red-500' : '' }}" style="width: {{ $pct($stats['storage_bytes'], $maxStorage) }}%"></span></div>
        @else
            <p class="mt-1 text-[13px] text-slate-500">Sans limite</p>
        @endif
    </div>
    <div class="card p-6">
        <p class="text-[13px] font-semibold text-slate-500">Documents</p>
        <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ number_format($stats['documents'], 0, ',', ' ') }}</p>
        <p class="mt-1 text-[13px] text-slate-500">+ {{ $stats['trashed'] }} en corbeille</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Utilisateurs --}}
    <div class="card lg:col-span-2 overflow-hidden" x-data="{ adding: false }">
        <div class="card-header">
            <div>
                <h2 class="card-title">Utilisateurs</h2>
                <p class="card-subtitle">{{ $users->count() }} compte(s) dans cette entreprise</p>
            </div>
            <button @click="adding = !adding" class="btn btn-sm" :class="adding ? 'btn-primary' : 'btn-light'"><i class="fa-solid fa-user-plus"></i> Ajouter</button>
        </div>

        <form x-show="adding" x-cloak x-transition method="POST" action="{{ route('super.organizations.users.store', $organization) }}"
              class="px-6 py-5 bg-slate-50/70 border-b border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="label">Nom complet</label>
                <input type="text" name="full_name" required class="field">
            </div>
            <div>
                <label class="label">Email</label>
                <input type="email" name="email" required class="field">
            </div>
            <div>
                <label class="label">Mot de passe provisoire</label>
                <input type="text" name="password" placeholder="8 caractères min." minlength="8" required class="field font-mono" autocomplete="off">
            </div>
            <div>
                <label class="label">Rôle</label>
                <select name="role_id" class="field">
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected($role->name === 'admin' && $users->isEmpty())>{{ $role->display_name ?? $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Service</label>
                <select name="department_id" required class="field">
                    <option value="">Choisir un service…</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) old('department_id') === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2 flex justify-end gap-2">
                <button type="button" @click="adding = false" class="btn btn-ghost">Annuler</button>
                <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Ajouter l'utilisateur</button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="data">
                <thead><tr><th>Nom</th><th>Rôles</th><th>État</th><th class="!text-right">Actions</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr x-data="{ pwd: false }">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="avatar {{ $user->is_active ? '' : '!bg-slate-100 !text-slate-400 !ring-slate-200' }}">{{ initials($user->full_name) }}</span>
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-900">{{ $user->full_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                </div>
                            </div>
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
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty">
                                <div class="empty-icon"><i class="fa-solid fa-user-group"></i></div>
                                <p class="text-sm font-bold text-slate-900">Aucun utilisateur</p>
                                <p class="text-sm text-slate-500 mt-1">Ajoutez le premier compte administrateur.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Informations + activité --}}
    <div class="space-y-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Informations</h2></div>
            <dl class="px-6 py-2">
                <div class="kv"><dt>Adresse</dt><dd>{{ $organization->address ?: '—' }}</dd></div>
                <div class="kv"><dt>Identifiant</dt><dd class="font-mono text-[13px]">{{ $organization->slug }}</dd></div>
                <div class="kv"><dt>Préfixe</dt><dd class="font-mono text-[13px]">{{ $organization->reference_prefix }}</dd></div>
                <div class="kv"><dt>Stockage MinIO</dt><dd class="font-mono text-[13px] truncate">{{ $organization->hasDedicatedBucket() ? $organization->storage_bucket : $organization->storageBucket() . '/' . $organization->storagePrefix() . '/' }}</dd></div>
                <div class="kv"><dt>Serveur</dt><dd class="font-mono text-[13px] truncate">{{ $organization->hasDedicatedServer() ? $organization->storage_endpoint : 'plateforme' }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('super.organizations.storage.check', $organization) }}" class="px-6 pb-2">
                @csrf
                <button class="btn btn-light w-full"><i class="fa-solid fa-plug-circle-check"></i> Vérifier le stockage</button>
            </form>
            {{-- Export complet (réversibilité : départ d'un client, audit…) --}}
            @php $lastExport = \App\Models\OrganizationExport::withoutGlobalScopes()->where('organization_id', $organization->id)->latest()->first(); @endphp
            <div class="px-6 pb-4 space-y-2">
                <form method="POST" action="{{ route('super.organizations.exports.store', $organization) }}">
                    @csrf
                    <button class="btn btn-light w-full"><i class="fa-solid fa-file-zipper"></i> Exporter toutes les données</button>
                </form>
                @if($lastExport)
                <p class="text-xs text-slate-500 text-center">
                    @if($lastExport->isReady())
                        <a href="{{ route('super.organizations.exports.download', [$organization, $lastExport]) }}" class="font-semibold text-orange-600 hover:underline" data-no-loader>Télécharger l'export du {{ $lastExport->created_at->format('d/m/Y H:i') }}</a> ({{ formatBytes($lastExport->size) }})
                    @elseif(in_array($lastExport->status, ['pending', 'running']))
                        Export en préparation…
                    @elseif($lastExport->status === 'failed')
                        Dernier export en échec.
                    @endif
                </p>
                @endif
            </div>
            @if($organization->notes)
                <div class="mx-6 mb-6 flex gap-3 rounded-xl bg-orange-50/60 border border-orange-100 px-4 py-3 text-sm text-slate-700">
                    <i class="fa-solid fa-note-sticky text-orange-500 mt-0.5"></i>
                    <p class="whitespace-pre-line">{{ $organization->notes }}</p>
                </div>
            @endif
        </div>
        <div class="card overflow-hidden">
            <div class="card-header"><h2 class="card-title">Activité</h2></div>
            @include('super.activity._list', ['logs' => $activity])
        </div>
    </div>
</div>

{{-- Historique des abonnements --}}
<div class="card mt-6 overflow-hidden">
    <div class="card-header">
        <div>
            <h2 class="card-title">Historique des abonnements</h2>
            <p class="card-subtitle">Toutes les périodes, paiements et annulations</p>
        </div>
        <a href="{{ route('super.subscriptions.create', $organization) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-plus"></i> Nouvel abonnement</a>
    </div>
    @include('super.subscriptions._table', ['subscriptions' => $subscriptions, 'showOrganization' => false])
</div>

{{-- Zone dangereuse --}}
<div class="mt-6 rounded-2xl border border-red-200 bg-white p-6" x-data="{ open: false }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex gap-4">
            <span class="w-10 h-10 rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100 flex items-center justify-center shrink-0"><i class="fa-solid fa-triangle-exclamation text-sm"></i></span>
            <div>
                <h2 class="text-base font-bold text-slate-900">Supprimer définitivement l'entreprise</h2>
                <p class="text-sm text-slate-500 mt-0.5">Supprime ses {{ $stats['documents'] + $stats['trashed'] }} documents (fichiers MinIO compris), ses utilisateurs et ses abonnements. Action irréversible.</p>
            </div>
        </div>
        <button @click="open = !open" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
    </div>
    <form x-show="open" x-cloak x-transition method="POST" action="{{ route('super.organizations.destroy', $organization) }}" class="mt-5 pt-5 border-t border-red-100 flex flex-wrap gap-3 items-end">
        @csrf @method('DELETE')
        <div class="flex-1 min-w-[240px]">
            <label class="label">Tapez « <span class="font-semibold">{{ $organization->name }}</span> » pour confirmer</label>
            <input type="text" name="confirm_name" required class="field focus:!border-red-400 focus:!ring-red-500/10" autocomplete="off">
        </div>
        <button class="btn btn-danger-solid">Supprimer définitivement</button>
    </form>
</div>
@endsection
