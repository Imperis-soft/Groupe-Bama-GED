@extends('layouts.super')
@section('title', 'Nouvelle entreprise')

@section('header')
<a href="{{ route('super.organizations.index') }}" class="back-link"><i class="fa-solid fa-arrow-left text-xs"></i> Entreprises</a>
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-wand-magic-sparkles"></i> Nouveau client</p>
        <h1 class="page-title">Nouvelle entreprise</h1>
        <p class="page-subtitle">Créez l'espace client, son administrateur et sa première période d'abonnement.</p>
    </div>
</div>
@endsection

@section('content')
@php
    $planData = $plans->mapWithKeys(fn ($p) => [$p->id => ['name' => $p->name, 'price' => $p->price, 'months' => $p->billing_months, 'currency' => $p->currency]]);
@endphp


<form method="POST" action="{{ route('super.organizations.store') }}"
      x-data="{
          prefix: @js(old('reference_prefix', '')), prefixTouched: @js((bool) old('reference_prefix')),
          bucket: @js(old('storage_bucket', '')), bucketTouched: @js((bool) old('storage_bucket')),
          dedicated: @js((bool) old('storage_dedicated')),
          mode: @js(old('mode', 'trial')),
          plans: @js($planData), planId: @js((string) old('plan_id', $plans->first()?->id)),
          months: @js((int) old('months', 12)), amount: @js(old('amount')),
          suggested() { const p = this.plans[this.planId]; return p ? Math.round(p.price * this.months / p.months) : 0; },
          currency() { return this.plans[this.planId]?.currency ?? 'XOF'; },
      }"
      x-init="if (amount === null) amount = suggested(); $watch('planId', () => amount = suggested()); $watch('months', () => amount = suggested())"
      class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6 items-start">
    @csrf

    <div class="space-y-6 min-w-0">
        {{-- 1. Entreprise --}}
        <section class="card">
            <div class="card-header justify-start">
                <span class="section-icon font-semibold text-sm">1</span>
                <div>
                    <h2 class="card-title">Entreprise</h2>
                    <p class="card-subtitle">Identité et coordonnées du client</p>
                </div>
            </div>
            <div class="card-body">
                @include('super.organizations._fields', ['organization' => null])
            </div>
        </section>

        {{-- 2. Stockage MinIO --}}
        <section class="card">
            <div class="card-header justify-start">
                <span class="section-icon font-semibold text-sm">2</span>
                <div>
                    <h2 class="card-title">Stockage MinIO</h2>
                    <p class="card-subtitle">Bucket propre à l'entreprise, où seront stockés tous ses documents</p>
                </div>
            </div>
            <div class="card-body">
                @include('super.organizations._storage', ['organization' => null])
            </div>
        </section>

        {{-- 3. Administrateur --}}
        <section class="card">
            <div class="card-header justify-start">
                <span class="section-icon font-semibold text-sm">3</span>
                <div>
                    <h2 class="card-title">Administrateur de l'entreprise</h2>
                    <p class="card-subtitle">Il pourra créer les autres utilisateurs de son entreprise.</p>
                </div>
            </div>
            <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="label">Nom complet <span class="text-orange-600">*</span></label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" required class="field">
                </div>
                <div>
                    <label class="label">Email de connexion <span class="text-orange-600">*</span></label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required class="field">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Mot de passe provisoire <span class="text-orange-600">*</span></label>
                    <div class="relative">
                        <i class="fa-solid fa-key absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-slate-400"></i>
                        <input type="text" name="admin_password" value="{{ old('admin_password') }}" required minlength="8" class="field !pl-10 font-mono" autocomplete="off">
                    </div>
                    <p class="hint">8 caractères minimum. Communiquez-le de façon sécurisée ; l'administrateur pourra le changer depuis son profil.</p>
                </div>
            </div>
        </section>

        {{-- 4. Abonnement --}}
        <section class="card">
            <div class="card-header justify-start">
                <span class="section-icon font-semibold text-sm">4</span>
                <div>
                    <h2 class="card-title">Abonnement</h2>
                    <p class="card-subtitle">Offre et première période d'accès</p>
                </div>
            </div>
            <div class="card-body space-y-5">
                <div>
                    <label class="label">Offre <span class="text-orange-600">*</span></label>
                    <select name="plan_id" x-model="planId" class="field">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->formattedPrice() }} / {{ $plan->periodLabel() }}
                                ({{ $plan->max_users ? $plan->max_users . ' utilisateurs' : 'utilisateurs illimités' }},
                                 {{ $plan->max_storage_mb ? formatBytes($plan->max_storage_mb * 1024 * 1024) : 'stockage illimité' }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="relative flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                           :class="mode === 'trial' ? 'border-orange-400 bg-orange-50/50 ring-4 ring-orange-500/10' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="mode" value="trial" x-model="mode" class="check !rounded-full mt-0.5">
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Essai gratuit</span>
                            <span class="block text-xs text-slate-500 mt-0.5">{{ config('saas.trial_days') }} jours d'accès complet</span>
                        </span>
                    </label>
                    <label class="relative flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                           :class="mode === 'paid' ? 'border-orange-400 bg-orange-50/50 ring-4 ring-orange-500/10' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="mode" value="paid" x-model="mode" class="check !rounded-full mt-0.5">
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Abonnement payé</span>
                            <span class="block text-xs text-slate-500 mt-0.5">Paiement déjà encaissé</span>
                        </span>
                    </label>
                </div>

                <template x-if="mode === 'paid'">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-1">
                        <div>
                            <label class="label">Durée (mois) <span class="text-orange-600">*</span></label>
                            <input type="number" name="months" x-model.number="months" min="1" max="60" class="field">
                        </div>
                        <div>
                            <label class="label">Montant encaissé (<span x-text="currency()"></span>)</label>
                            <input type="number" name="amount" x-model.number="amount" min="0" class="field tabular-nums">
                            <p class="hint">Tarif de l'offre : <span class="font-medium text-slate-700" x-text="suggested().toLocaleString('fr-FR')"></span> <span x-text="currency()"></span></p>
                        </div>
                        <div>
                            <label class="label">Moyen de paiement</label>
                            <select name="payment_method" class="field">
                                @foreach(\App\Models\Subscription::PAYMENT_METHODS as $value => $label)
                                    @continue(in_array($value, ['licence']))
                                    <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Référence du paiement</label>
                            <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="N° de transaction, de chèque…" class="field">
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    {{-- Récapitulatif --}}
    <aside class="card lg:sticky lg:top-24">
        <div class="card-header"><h2 class="card-title">Récapitulatif</h2></div>
        <div class="card-body">
            <dl>
                <div class="kv"><dt>Offre</dt><dd x-text="plans[planId]?.name ?? '—'"></dd></div>
                <div class="kv"><dt>Formule</dt><dd x-text="mode === 'trial' ? 'Essai gratuit' : 'Payée'"></dd></div>
                <div class="kv"><dt>Durée</dt><dd x-text="mode === 'trial' ? '{{ config('saas.trial_days') }} jours' : months + ' mois'"></dd></div>
                <div class="kv"><dt>Préfixe</dt><dd class="font-mono" x-text="prefix || '—'"></dd></div>
                <div class="kv"><dt>Stockage</dt><dd class="font-mono truncate" x-text="bucket || @js(config('filesystems.disks.s3.bucket') . '/<dossier privé>')"></dd></div>
            </dl>
            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500">Montant</p>
                <p class="text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums mt-0.5">
                    <span x-text="mode === 'trial' ? '0' : Number(amount || 0).toLocaleString('fr-FR')"></span>
                    <span class="text-sm font-medium text-slate-500" x-text="currency()"></span>
                </p>
            </div>
            <button class="btn btn-primary w-full mt-5"><i class="fa-solid fa-check"></i> Créer l'entreprise</button>
            <a href="{{ route('super.organizations.index') }}" class="btn btn-ghost w-full mt-2">Annuler</a>
        </div>
    </aside>
</form>
@endsection
