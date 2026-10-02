@extends('layouts.super')
@section('title', 'Offres')

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-layer-group"></i> Commercial</p>
        <h1 class="page-title">Offres</h1>
        <p class="page-subtitle">Les limites s'appliquent dès l'enregistrement aux entreprises abonnées.</p>
    </div>
    <a href="{{ route('super.plans.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nouvelle offre</a>
</div>
@endsection

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    @forelse($plans as $plan)
        <div class="card flex flex-col overflow-hidden transition hover:shadow-pop {{ $plan->is_active ? 'hover:border-orange-200' : 'opacity-70' }}">
            <div class="h-1 {{ $plan->is_active ? 'bg-gradient-to-r from-orange-400 to-orange-600' : 'bg-slate-200' }}"></div>
            <div class="p-6 flex-1 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $plan->name }}</h2>
                        @if($plan->description)<p class="text-sm text-slate-500 mt-0.5">{{ $plan->description }}</p>@endif
                    </div>
                    @if($plan->is_active)
                        <span class="badge badge-green"><span class="dot"></span> Proposée</span>
                    @else
                        <span class="badge badge-slate">Désactivée</span>
                    @endif
                </div>

                <p class="mt-6 flex items-baseline gap-1.5">
                    <span class="text-[32px] leading-none font-extrabold tracking-tight text-slate-900 tabular-nums">{{ $plan->formattedPrice() }}</span>
                    <span class="text-sm text-slate-500">/ {{ $plan->periodLabel() }}</span>
                </p>

                <div class="mt-6 pt-6 border-t border-slate-100 space-y-3 text-sm text-slate-600">
                    <p class="flex items-center gap-3"><span class="w-6 h-6 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-user-group text-[10px]"></i></span>{{ $plan->max_users ? $plan->max_users . ' utilisateurs' : 'Utilisateurs illimités' }}</p>
                    <p class="flex items-center gap-3"><span class="w-6 h-6 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-hard-drive text-[10px]"></i></span>{{ $plan->max_storage_mb ? formatBytes($plan->max_storage_mb * 1024 * 1024) : 'Stockage illimité' }}</p>
                    @foreach($plan->features ?? [] as $feature)
                        <p class="flex items-center gap-3"><span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-check text-[10px]"></i></span>{{ $feature }}</p>
                    @endforeach
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between gap-3">
                <span class="text-[13px] text-slate-500 whitespace-nowrap"><span class="font-bold text-slate-900 tabular-nums">{{ $plan->current_count }}</span> en cours</span>
                <div class="flex gap-2">
                    <a href="{{ route('super.plans.edit', $plan) }}" class="btn btn-light btn-sm"><i class="fa-solid fa-pen"></i> Modifier</a>
                    <form method="POST" action="{{ route('super.plans.destroy', $plan) }}" onsubmit="return confirm('Supprimer (ou désactiver) cette offre ?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm btn-icon" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card empty md:col-span-2 xl:col-span-3">
            <div class="empty-icon"><i class="fa-solid fa-layer-group"></i></div>
            <p class="text-sm font-bold text-slate-900">Aucune offre</p>
            <p class="text-sm text-slate-500 mt-1">Créez votre première offre commerciale.</p>
            <a href="{{ route('super.plans.create') }}" class="btn btn-primary mt-5"><i class="fa-solid fa-plus"></i> Nouvelle offre</a>
        </div>
    @endforelse
</div>
@endsection
