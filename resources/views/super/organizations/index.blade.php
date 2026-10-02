@extends('layouts.super')
@section('title', 'Entreprises')

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-building"></i> Pilotage</p>
        <h1 class="page-title">Entreprises</h1>
        <p class="page-subtitle">{{ $organizations->total() }} entreprise(s) cliente(s) sur la plateforme.</p>
    </div>
    <a href="{{ route('super.organizations.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle entreprise</a>
</div>
@endsection

@section('content')

<form method="GET" class="filters">
    <div class="relative flex-1 min-w-[220px] max-w-sm">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-[13px] text-slate-400"></i>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom, email…" class="field !pl-10">
    </div>
    <select name="status" class="field !w-auto" onchange="this.form.submit()">
        <option value="">Tous les états</option>
        <option value="active" @selected(request('status') === 'active')>Actives</option>
        <option value="expired" @selected(request('status') === 'expired')>Sans abonnement en cours</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Suspendues</option>
    </select>
    <button class="btn btn-light"><i class="fa-solid fa-sliders"></i> Filtrer</button>
    @if(request('q') || request('status'))
        <a href="{{ route('super.organizations.index') }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-xmark"></i> Réinitialiser</a>
    @endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data">
            <thead><tr><th>Entreprise</th><th>État</th><th>Offre</th><th>Utilisateurs</th><th>Documents</th><th>Créée le</th><th></th></tr></thead>
            <tbody>
            @forelse($organizations as $organization)
                @php($current = $organization->activeSubscription())
                @php($maxUsers = $current?->plan->max_users)
                <tr class="cursor-pointer" onclick="window.location='{{ route('super.organizations.show', $organization) }}'">
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="avatar">{{ initials($organization->name) }}</span>
                            <div class="min-w-0">
                                <a href="{{ route('super.organizations.show', $organization) }}" class="font-bold text-slate-900 hover:text-orange-600">{{ $organization->name }}</a>
                                <p class="text-xs text-slate-500 truncate">{{ $organization->email ?: $organization->slug }}</p>
                            </div>
                        </div>
                    </td>
                    <td>@include('super.organizations._status')</td>
                    <td class="text-slate-700">{{ $current?->plan->name ?? '—' }}</td>
                    <td class="min-w-[130px]">
                        <p class="tabular-nums text-slate-700">{{ $organization->users_count }}@if($maxUsers)<span class="text-slate-400"> / {{ $maxUsers }}</span>@endif</p>
                        @if($maxUsers)
                            @php($p = min(100, (int) round($organization->users_count * 100 / $maxUsers)))
                            <div class="meter mt-1.5 max-w-[100px]"><span class="{{ $p >= 100 ? '!bg-none !bg-red-500' : '' }}" style="width: {{ $p }}%"></span></div>
                        @endif
                    </td>
                    <td class="tabular-nums text-slate-700">{{ number_format($organization->documents_count, 0, ',', ' ') }}</td>
                    <td class="whitespace-nowrap text-slate-500">{{ $organization->created_at->format('d/m/Y') }}</td>
                    <td class="text-right"><i class="fa-solid fa-chevron-right text-[11px] text-slate-300"></i></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty">
                            <div class="empty-icon"><i class="fa-solid fa-building"></i></div>
                            <p class="text-sm font-bold text-slate-900">Aucune entreprise trouvée</p>
                            <p class="text-sm text-slate-500 mt-1">Modifiez vos filtres ou créez une nouvelle entreprise.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $organizations->links() }}
@endsection
