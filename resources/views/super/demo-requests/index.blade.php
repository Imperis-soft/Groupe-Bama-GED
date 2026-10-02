@extends('layouts.super')
@section('title', 'Demandes')

@section('header')
<div class="page-header">
    <div>
        <p class="eyebrow mb-3"><i class="fa-solid fa-inbox"></i> Commercial</p>
        <h1 class="page-title">Demandes de démo</h1>
        <p class="page-subtitle">Prospects reçus depuis le formulaire de la page d'accueil.</p>
    </div>
</div>
@endsection

@section('content')
@php
    $statusBadges  = ['new' => 'badge-orange', 'contacted' => 'badge-amber', 'won' => 'badge-green', 'lost' => 'badge-slate'];
    $formulaBadges = ['single_entity' => 'badge-dark', 'saas' => 'badge-orange'];
@endphp


<div class="filters">
    <div class="tabs">
        <a href="{{ route('super.demo-requests.index') }}" class="tab {{ request('status') ? '' : 'active' }}">
            Toutes <span class="count">{{ $counts->sum() }}</span>
        </a>
        @foreach(\App\Models\DemoRequest::STATUSES as $value => $label)
            <a href="{{ route('super.demo-requests.index', ['status' => $value]) }}" class="tab {{ request('status') === $value ? 'active' : '' }}">
                {{ $label }} <span class="count">{{ $counts[$value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>
</div>

<div class="space-y-4">
    @forelse($demoRequests as $demo)
        <article class="card overflow-hidden" x-data="{ edit: false }">
            <div class="p-6">
                <div class="flex flex-wrap items-start gap-4">
                    <span class="avatar avatar-lg !rounded-xl">{{ initials($demo->company) }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-extrabold tracking-tight text-slate-900">{{ $demo->company }}</h2>
                            <span class="badge {{ $statusBadges[$demo->status] ?? 'badge-slate' }}"><span class="dot"></span>{{ $demo->statusLabel() }}</span>
                            <span class="badge {{ $formulaBadges[$demo->formula] ?? 'badge-slate' }}">{{ $demo->formulaLabel() }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-slate-600">
                            <span><i class="fa-regular fa-user w-4 text-slate-400"></i> {{ $demo->contact_name }}</span>
                            <a href="mailto:{{ $demo->email }}" class="hover:text-orange-600"><i class="fa-regular fa-envelope w-4 text-slate-400"></i> {{ $demo->email }}</a>
                            @if($demo->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $demo->phone) }}" class="hover:text-orange-600"><i class="fa-solid fa-phone w-4 text-slate-400 text-[12px]"></i> {{ $demo->phone }}</a>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                            <span title="{{ $demo->created_at->format('d/m/Y H:i') }}"><i class="fa-regular fa-clock mr-1"></i>{{ $demo->created_at->diffForHumans() }}</span>
                            @if($demo->company_size)<span><i class="fa-solid fa-user-group mr-1"></i>{{ $demo->company_size }} employés</span>@endif
                            @if($demo->sector)<span><i class="fa-solid fa-briefcase mr-1"></i>{{ $demo->sector }}</span>@endif
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="mailto:{{ $demo->email }}?subject={{ rawurlencode('Votre demande de démo ' . config('saas.platform_name')) }}" class="btn btn-light btn-sm">
                            <i class="fa-solid fa-reply"></i> Répondre
                        </a>
                        <button @click="edit = !edit" class="btn btn-sm" :class="edit ? 'btn-primary' : 'btn-light'">
                            <i class="fa-solid fa-pen"></i> Suivi
                        </button>
                    </div>
                </div>

                @if($demo->message)
                    <blockquote class="mt-5 rounded-xl bg-slate-50 border-l-2 border-slate-200 px-4 py-3 text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ $demo->message }}</blockquote>
                @endif
                @if($demo->notes)
                    <div class="mt-3 flex gap-3 rounded-xl bg-orange-50/60 border border-orange-100 px-4 py-3 text-sm text-slate-700">
                        <i class="fa-solid fa-note-sticky text-orange-500 mt-0.5"></i>
                        <p class="whitespace-pre-line"><span class="font-semibold text-slate-900">Suivi :</span> {{ $demo->notes }}</p>
                    </div>
                @endif
            </div>

            <form x-show="edit" x-cloak x-transition method="POST" action="{{ route('super.demo-requests.update', $demo) }}"
                  class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-[180px_1fr_auto] gap-3">
                @csrf @method('PUT')
                <select name="status" class="field">
                    @foreach(\App\Models\DemoRequest::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected($demo->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="notes" value="{{ $demo->notes }}" placeholder="Notes de suivi (rappel, devis envoyé…)" class="field">
                <button class="btn btn-primary"><i class="fa-solid fa-check"></i> Enregistrer</button>
            </form>
        </article>
    @empty
        <div class="card empty">
            <div class="empty-icon"><i class="fa-solid fa-inbox"></i></div>
            <p class="text-sm font-bold text-slate-900">Aucune demande</p>
            <p class="text-sm text-slate-500 mt-1">Les demandes envoyées depuis le site apparaîtront ici.</p>
        </div>
    @endforelse
</div>

{{ $demoRequests->links() }}
@endsection
