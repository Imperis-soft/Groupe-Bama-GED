@extends('layouts.super')
@section('title', 'Journal')

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-clock-rotate-left"></i> Administration</p>
        <h1 class="page-title">Journal de la plateforme</h1>
        <p class="page-subtitle">Actions du super admin : entreprises, abonnements, offres, comptes et accès aux espaces clients.</p>
    </div>
</div>
@endsection

@section('content')

<form method="GET" class="filters">
    <select name="organization" class="field !w-auto min-w-[240px]" onchange="this.form.submit()">
        <option value="">Toutes les entreprises</option>
        @foreach($organizations as $org)
            <option value="{{ $org->id }}" @selected((string) request('organization') === (string) $org->id)>{{ $org->name }}</option>
        @endforeach
    </select>
    @if(request('organization'))
        <a href="{{ route('super.activity.index') }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-xmark"></i> Réinitialiser</a>
    @endif
</form>

<div class="card overflow-hidden">
    @include('super.activity._list')
</div>
{{ $logs->links() }}
@endsection
