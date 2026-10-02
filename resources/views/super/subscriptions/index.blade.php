@extends('layouts.super')
@section('title', 'Abonnements')

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-receipt"></i> Pilotage</p>
        <h1 class="page-title">Abonnements</h1>
        <p class="page-subtitle">{{ $subscriptions->total() }} période(s) d'abonnement enregistrée(s).</p>
    </div>
    <div class="card px-5 py-3 flex items-center gap-4">
        <span class="section-icon"><i class="fa-solid fa-wallet text-sm"></i></span>
        <div>
            <p class="text-xs text-slate-500">Total hors annulés</p>
            <p class="text-xl font-extrabold tracking-tight text-slate-900 tabular-nums">{{ formatMoney($totalAmount) }}</p>
        </div>
    </div>
</div>
@endsection

@section('content')

<form method="GET" class="filters">
    <select name="organization" class="field !w-auto" onchange="this.form.submit()">
        <option value="">Toutes les entreprises</option>
        @foreach($organizations as $org)
            <option value="{{ $org->id }}" @selected((string) request('organization') === (string) $org->id)>{{ $org->name }}</option>
        @endforeach
    </select>
    <select name="status" class="field !w-auto" onchange="this.form.submit()">
        <option value="">Tous les états</option>
        <option value="current" @selected(request('status') === 'current')>En cours</option>
        <option value="trial" @selected(request('status') === 'trial')>Essais</option>
        <option value="expired" @selected(request('status') === 'expired')>Expirés</option>
        <option value="cancelled" @selected(request('status') === 'cancelled')>Annulés</option>
    </select>
    <select name="year" class="field !w-auto" onchange="this.form.submit()">
        <option value="">Toutes les années (paiement)</option>
        @for($y = now()->year; $y >= now()->year - 4; $y--)
            <option value="{{ $y }}" @selected((string) request('year') === (string) $y)>{{ $y }}</option>
        @endfor
    </select>
    @if(request('organization') || request('status') || request('year'))
        <a href="{{ route('super.subscriptions.index') }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-xmark"></i> Réinitialiser</a>
    @endif
</form>

<div class="card overflow-hidden">
    @include('super.subscriptions._table', ['showOrganization' => true])
</div>
{{ $subscriptions->links() }}
@endsection
