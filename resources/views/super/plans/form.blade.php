@extends('layouts.super')
@section('title', $plan->exists ? 'Modifier ' . $plan->name : 'Nouvelle offre')

@section('header')
<a href="{{ route('super.plans.index') }}" class="back-link"><i class="fa-solid fa-arrow-left text-xs"></i> Offres</a>
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-layer-group"></i> Offre commerciale</p>
        <h1 class="page-title">{{ $plan->exists ? 'Modifier l\'offre ' . $plan->name : 'Nouvelle offre' }}</h1>
        <p class="page-subtitle">Tarif, limites et arguments affichés sur la page d'accueil.</p>
    </div>
</div>
@endsection

@section('content')

<form method="POST" action="{{ $plan->exists ? route('super.plans.update', $plan) : route('super.plans.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start"
      x-data="{
          name: @js(old('name', $plan->name) ?? ''),
          description: @js(old('description', $plan->description) ?? ''),
          features: @js(old('features', implode("\n", $plan->features ?? []))),
          price: @js((string) old('price', $plan->price ?? 0)),
          currency: @js(old('currency', $plan->currency) ?? 'XOF'),
          months: @js((int) old('billing_months', $plan->billing_months ?: 1)),
          maxUsers: @js((string) old('max_users', $plan->max_users)),
          maxStorage: @js((string) old('max_storage_mb', $plan->max_storage_mb)),
          active: @js((bool) old('is_active', $plan->exists ? $plan->is_active : true)),
          get periodLabel() { return { 1: 'mois', 3: 'trimestre', 6: 'semestre', 12: 'an' }[this.months] || 'mois'; },
          get featureList() { return this.features.split('\n').map(f => f.trim()).filter(Boolean); },
          get storageLabel() { const mb = parseInt(this.maxStorage); if (!mb) return 'Stockage illimité'; return mb >= 1024 ? (Math.round(mb / 102.4) / 10) + ' Go de stockage' : mb + ' Mo de stockage'; },
          formatPrice(v) { return (parseInt(v) || 0).toLocaleString('fr-FR'); },
      }">
    @csrf
    @if($plan->exists) @method('PUT') @endif

    <div class="lg:col-span-2 space-y-6">

    <section class="card">
        <div class="card-header justify-start">
            <span class="section-icon"><i class="fa-solid fa-tag text-sm"></i></span>
            <div>
                <h2 class="card-title">Présentation</h2>
                <p class="card-subtitle">Nom et description de l'offre</p>
            </div>
        </div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="label">Nom <span class="text-orange-600">*</span></label>
                <input type="text" name="name" x-model="name" required class="field">
            </div>
            <div>
                <label class="label">Identifiant (slug)</label>
                <input type="text" name="slug" value="{{ old('slug', $plan->slug) }}" placeholder="généré depuis le nom" class="field font-mono">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Description courte</label>
                <input type="text" name="description" x-model="description" class="field">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Arguments commerciaux</label>
                <textarea name="features" rows="4" class="field" placeholder="Un argument par ligne" x-model="features"></textarea>
                <p class="hint">Un argument par ligne.</p>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header justify-start">
            <span class="section-icon"><i class="fa-solid fa-coins text-sm"></i></span>
            <div>
                <h2 class="card-title">Tarification</h2>
                <p class="card-subtitle">Prix facturé pour chaque période</p>
            </div>
        </div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="label">Prix par période <span class="text-orange-600">*</span></label>
                <input type="number" name="price" x-model="price" min="0" required class="field tabular-nums">
            </div>
            <div>
                <label class="label">Devise <span class="text-orange-600">*</span></label>
                <input type="text" name="currency" x-model="currency" maxlength="3" required class="field uppercase">
            </div>
            <div>
                <label class="label">Période <span class="text-orange-600">*</span></label>
                <select name="billing_months" class="field" x-model.number="months">
                    @foreach([1 => 'Mois', 3 => 'Trimestre', 6 => 'Semestre', 12 => 'Année'] as $months => $label)
                        <option value="{{ $months }}" @selected((int) old('billing_months', $plan->billing_months) === $months)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header justify-start">
            <span class="section-icon"><i class="fa-solid fa-gauge text-sm"></i></span>
            <div>
                <h2 class="card-title">Limites et affichage</h2>
                <p class="card-subtitle">Laissez vide pour ne pas limiter</p>
            </div>
        </div>
        <div class="card-body grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="label">Utilisateurs max.</label>
                <input type="number" name="max_users" x-model="maxUsers" min="1" placeholder="Illimité" class="field">
            </div>
            <div>
                <label class="label">Stockage max. (Mo)</label>
                <input type="number" name="max_storage_mb" x-model="maxStorage" min="1" placeholder="Illimité" class="field">
                <p class="hint">1 Go = 1024 Mo</p>
            </div>
            <div>
                <label class="label">Ordre d'affichage</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0" class="field">
            </div>
            <label class="sm:col-span-3 flex items-start gap-3 rounded-xl border border-slate-200 p-4 cursor-pointer hover:border-slate-300 transition">
                <input type="checkbox" name="is_active" value="1" x-model="active" class="check mt-0.5">
                <span>
                    <span class="block text-sm font-bold text-slate-900">Offre proposée aux nouvelles souscriptions</span>
                    <span class="block text-xs text-slate-500 mt-0.5">Décochée, l'offre reste valable pour les abonnements existants mais n'est plus affichée.</span>
                </span>
            </label>
        </div>
    </section>

    </div>

    {{-- ======= Aperçu ======= --}}
    <aside class="space-y-4 lg:sticky lg:top-24">
        <p class="kicker">Aperçu sur la page d'accueil</p>
        <div class="card p-6" :class="active ? '' : 'opacity-60'">
            <div class="flex items-start justify-between gap-3">
                <p class="text-lg font-extrabold tracking-tight text-slate-900" x-text="name || 'Nom de l\'offre'"></p>
                <span x-show="!active" x-cloak class="badge badge-slate">Masquée</span>
            </div>
            <p class="mt-1 text-sm text-slate-500 min-h-[20px]" x-text="description"></p>
            <p class="mt-5 flex items-baseline gap-1.5">
                <span class="stat" x-text="formatPrice(price)"></span>
                <span class="text-sm font-semibold text-slate-500" x-text="(currency || '').toUpperCase() + ' / ' + periodLabel"></span>
            </p>
            <ul class="mt-6 pt-5 border-t border-slate-100 space-y-2.5 text-sm text-slate-600">
                <li class="flex gap-2.5"><i class="fa-solid fa-check text-orange-600 text-xs mt-1"></i><span x-text="parseInt(maxUsers) ? maxUsers + ' utilisateur(s)' : 'Utilisateurs illimités'"></span></li>
                <li class="flex gap-2.5"><i class="fa-solid fa-check text-orange-600 text-xs mt-1"></i><span x-text="storageLabel"></span></li>
                <template x-for="feature in featureList">
                    <li class="flex gap-2.5"><i class="fa-solid fa-check text-orange-600 text-xs mt-1"></i><span x-text="feature"></span></li>
                </template>
            </ul>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('super.plans.index') }}" class="btn btn-light flex-1">Annuler</a>
            <button class="btn btn-primary flex-1"><i class="fa-solid fa-check"></i> Enregistrer</button>
        </div>
    </aside>
</form>
@endsection
