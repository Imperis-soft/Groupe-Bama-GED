@extends('layouts.super')
@section('title', 'Abonnement — ' . $organization->name)

@section('header')
<a href="{{ route('super.organizations.show', $organization) }}" class="back-link"><i class="fa-solid fa-arrow-left text-xs"></i> {{ $organization->name }}</a>
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-rotate"></i> Facturation</p>
        <h1 class="page-title">Nouvel abonnement</h1>
        <p class="page-subtitle">Enregistrez un renouvellement ou une nouvelle période pour {{ $organization->name }}.</p>
    </div>
</div>
@endsection

@section('content')
@php
    $planData = $plans->mapWithKeys(fn ($p) => [$p->id => ['name' => $p->name, 'price' => $p->price, 'months' => $p->billing_months, 'currency' => $p->currency]]);
    $defaultPlan = old('plan_id', $latest?->plan_id ?? $plans->first()?->id);
@endphp


<form method="POST" action="{{ route('super.subscriptions.store', $organization) }}"
      x-data="{
          plans: @js($planData), planId: @js((string) $defaultPlan),
          months: @js((int) old('months', $planData[$defaultPlan]['months'] ?? 1)), amount: @js(old('amount')),
          start: @js(old('starts_at', $defaultStart->toDateString())),
          suggested() { const p = this.plans[this.planId]; return p ? Math.round(p.price * this.months / p.months) : 0; },
          currency() { return this.plans[this.planId]?.currency ?? 'XOF'; },
          end() {
              if (!this.start || !this.months) return '';
              const d = new Date(this.start + 'T00:00:00'); d.setMonth(d.getMonth() + Number(this.months)); d.setDate(d.getDate() - 1);
              return d.toLocaleDateString('fr-FR');
          },
      }"
      x-init="if (amount === null) amount = suggested(); $watch('planId', () => amount = suggested()); $watch('months', () => amount = suggested())"
      class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-6 items-start">
    @csrf

    <div class="space-y-6 min-w-0">
        @if($latest)
            <div class="flex gap-3 rounded-2xl border border-slate-200/80 bg-white px-5 py-4 shadow-soft">
                <i class="fa-solid fa-clock-rotate-left text-slate-400 mt-0.5"></i>
                <p class="text-sm text-slate-600">
                    Dernière période : <span class="font-semibold text-slate-900">{{ $latest->plan->name }}</span>
                    jusqu'au <span class="font-semibold text-slate-900">{{ $latest->ends_at->format('d/m/Y') }}</span>
                    ({{ $latest->displayStatusLabel() }}).
                </p>
            </div>
        @endif

        <section class="card">
            <div class="card-header justify-start">
                <span class="section-icon"><i class="fa-solid fa-calendar-days text-sm"></i></span>
                <div>
                    <h2 class="card-title">Période</h2>
                    <p class="card-subtitle">Offre, date de début et durée</p>
                </div>
            </div>
            <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="label">Offre <span class="text-orange-600">*</span></label>
                    <select name="plan_id" x-model="planId" class="field">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — {{ $plan->formattedPrice() }} / {{ $plan->periodLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Début <span class="text-orange-600">*</span></label>
                    <input type="date" name="starts_at" x-model="start" required class="field">
                </div>
                <div>
                    <label class="label">Durée (mois) <span class="text-orange-600">*</span></label>
                    <input type="number" name="months" x-model.number="months" min="1" max="60" required class="field">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Type <span class="text-orange-600">*</span></label>
                    <select name="status" class="field">
                        <option value="active" @selected(old('status') !== 'trial')>Payé</option>
                        <option value="trial" @selected(old('status') === 'trial')>Essai / geste commercial</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header justify-start">
                <span class="section-icon"><i class="fa-solid fa-money-bill-wave text-sm"></i></span>
                <div>
                    <h2 class="card-title">Paiement</h2>
                    <p class="card-subtitle">Montant encaissé et justificatif</p>
                </div>
            </div>
            <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="label">Montant (<span x-text="currency()"></span>) <span class="text-orange-600">*</span></label>
                    <input type="number" name="amount" x-model.number="amount" min="0" required class="field tabular-nums">
                    <p class="hint">Tarif de l'offre : <span class="font-medium text-slate-700" x-text="suggested().toLocaleString('fr-FR')"></span> <span x-text="currency()"></span></p>
                </div>
                <div>
                    <label class="label">Moyen de paiement</label>
                    <select name="payment_method" class="field">
                        <option value="">—</option>
                        @foreach(\App\Models\Subscription::PAYMENT_METHODS as $value => $label)
                            @continue($value === 'licence')
                            <option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Date du paiement</label>
                    <input type="date" name="paid_at" value="{{ old('paid_at', today()->toDateString()) }}" class="field">
                </div>
                <div>
                    <label class="label">Référence du paiement</label>
                    <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="Orange Money, Wave, virement…" class="field">
                </div>
                <div class="sm:col-span-2">
                    <label class="label">Notes</label>
                    <textarea name="notes" rows="2" class="field">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>
    </div>

    {{-- Récapitulatif --}}
    <aside class="card lg:sticky lg:top-24">
        <div class="card-header"><h2 class="card-title">Récapitulatif</h2></div>
        <div class="card-body">
            <dl>
                <div class="kv"><dt>Entreprise</dt><dd class="truncate">{{ $organization->name }}</dd></div>
                <div class="kv"><dt>Offre</dt><dd x-text="plans[planId]?.name ?? '—'"></dd></div>
                <div class="kv"><dt>Du</dt><dd x-text="start ? new Date(start + 'T00:00:00').toLocaleDateString('fr-FR') : '—'"></dd></div>
                <div class="kv"><dt>Au</dt><dd x-text="end() || '—'"></dd></div>
            </dl>
            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-xs text-slate-500">Montant</p>
                <p class="text-2xl font-extrabold tracking-tight text-slate-900 tabular-nums mt-0.5">
                    <span x-text="Number(amount || 0).toLocaleString('fr-FR')"></span>
                    <span class="text-sm font-medium text-slate-500" x-text="currency()"></span>
                </p>
            </div>
            <button class="btn btn-primary w-full mt-5"><i class="fa-solid fa-check"></i> Enregistrer l'abonnement</button>
            <a href="{{ route('super.organizations.show', $organization) }}" class="btn btn-ghost w-full mt-2">Annuler</a>
        </div>
    </aside>
</form>
@endsection
