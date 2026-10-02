@extends('layouts.super')
@section('title', 'Modifier ' . $organization->name)

@section('header')
<a href="{{ route('super.organizations.show', $organization) }}" class="back-link"><i class="fa-solid fa-arrow-left text-xs"></i> {{ $organization->name }}</a>
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-pen"></i> Entreprise</p>
        <h1 class="page-title">Modifier l'entreprise</h1>
        <p class="page-subtitle">Informations générales et préfixe des références documentaires.</p>
    </div>
</div>
@endsection

@section('content')

<form method="POST" action="{{ route('super.organizations.update', $organization) }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start"
      x-data="{ prefix: @js(old('reference_prefix', $organization->reference_prefix)), prefixTouched: true,
                bucket: @js(old('storage_bucket', $organization->storage_bucket)), bucketTouched: true,
                dedicated: @js((bool) old('storage_dedicated', $organization->hasDedicatedServer())) }">
    @csrf @method('PUT')
    <div class="card lg:col-span-2">
        <div class="card-header justify-start">
            <span class="section-icon"><i class="fa-solid fa-building text-sm"></i></span>
            <div>
                <h2 class="card-title">Entreprise</h2>
                <p class="card-subtitle">Coordonnées et identité</p>
            </div>
        </div>
        <div class="card-body">
            @include('super.organizations._fields')
            <div class="mt-5 flex gap-3 rounded-xl bg-amber-50/70 border border-amber-100 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <p>Changer le préfixe ne modifie que les références des nouveaux documents.</p>
            </div>
        </div>
        <div class="card-header justify-start border-t border-slate-100">
            <span class="section-icon"><i class="fa-solid fa-database text-sm"></i></span>
            <div>
                <h2 class="card-title">Stockage MinIO</h2>
                <p class="card-subtitle">Bucket et serveur où sont stockés les documents de l'entreprise</p>
            </div>
        </div>
        <div class="card-body">
            @include('super.organizations._storage', ['organization' => $organization, 'bucketLocked' => $bucketLocked])
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl">
            <a href="{{ route('super.organizations.show', $organization) }}" class="btn btn-light">Annuler</a>
            <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
        </div>
    </div>

    <aside class="card p-6 lg:sticky lg:top-24">
        <div class="flex items-center gap-3">
            <span class="avatar">{{ initials($organization->name) }}</span>
            <div class="min-w-0">
                <p class="text-[15px] font-bold text-slate-900 truncate">{{ $organization->name }}</p>
                <p class="text-xs text-slate-500 font-mono truncate">{{ $organization->slug }}</p>
            </div>
        </div>
        <dl class="mt-5">
            <div class="kv"><dt>Préfixe actuel</dt><dd class="font-mono">{{ $organization->reference_prefix }}</dd></div>
            <div class="kv"><dt>Bucket</dt><dd class="font-mono truncate">{{ $organization->hasDedicatedBucket() ? $organization->storage_bucket : $organization->storageBucket() . '/' . $organization->storagePrefix() . '/' }}</dd></div>
            <div class="kv"><dt>Créée le</dt><dd>{{ $organization->created_at?->format('d/m/Y') }}</dd></div>
            <div class="kv"><dt>Statut</dt><dd>{{ $organization->isSuspended() ? 'Suspendue' : 'Active' }}</dd></div>
        </dl>
        <a href="{{ route('super.organizations.show', $organization) }}" class="btn btn-light w-full mt-5"><i class="fa-solid fa-arrow-up-right-from-square"></i> Voir la fiche</a>
    </aside>
</form>
@endsection
