@extends('layouts.guest')

@section('title', 'Vérification de document')

@php
    $org      = $document->organization;
    $orgName  = $org?->name ?? config('saas.platform_name');
    $contacts = collect([$org?->address, $org?->phone, $org?->email])->filter();
    $tone = [
        'success' => ['from-emerald-500 to-green-600', 'fa-circle-check', 'text-emerald-50'],
        'info'    => ['from-sky-500 to-blue-600', 'fa-box-archive', 'text-sky-50'],
        'warning' => ['from-amber-500 to-orange-600', 'fa-triangle-exclamation', 'text-amber-50'],
        'danger'  => ['from-red-500 to-rose-600', 'fa-ban', 'text-red-50'],
    ][$state['level']];
    $masked = $document->is_confidential;
@endphp

@section('content')
<meta name="referrer" content="no-referrer">
<div class="min-h-screen bg-slate-100">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-4 py-8 text-center">
            <p class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 break-words">{{ mb_strtoupper($orgName) }}</p>
            @if($contacts->isNotEmpty())
                <div class="mt-3 flex flex-wrap justify-center gap-x-5 gap-y-1 text-sm text-slate-600">
                    @if($org?->address)<span><i class="fas fa-location-dot text-orange-600 mr-1.5"></i>{{ $org->address }}</span>@endif
                    @if($org?->phone)<span><i class="fas fa-phone text-orange-600 mr-1.5"></i>{{ $org->phone }}</span>@endif
                    @if($org?->email)<span><i class="fas fa-envelope text-orange-600 mr-1.5"></i>{{ $org->email }}</span>@endif
                </div>
            @endif
            <p class="mt-4 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                <i class="fas fa-shield-halved"></i>Vérification d'authenticité
            </p>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-8 space-y-6">
        {{-- Verdict --}}
        <section class="rounded-2xl shadow-lg overflow-hidden bg-gradient-to-r {{ $tone[0] }} text-white px-6 py-6 flex items-center gap-5">
            <i class="fas {{ $tone[1] }} text-4xl shrink-0"></i>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-wide">{{ $state['title'] }}</h1>
                <p class="{{ $tone[2] }} text-sm sm:text-base mt-1">{{ $state['message'] }}</p>
            </div>
        </section>

        {{-- Comparaison avec le papier --}}
        <section class="bg-white rounded-2xl shadow border border-slate-200 p-6">
            <h2 class="font-bold text-slate-800 mb-1"><i class="fas fa-magnifying-glass text-orange-600 mr-2"></i>Comparez avec le document en votre possession</h2>
            <p class="text-sm text-slate-500 mb-5">Ces informations doivent être <strong>identiques</strong> à celles imprimées sur le document. Au moindre écart, considérez-le comme suspect.</p>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Référence</p>
                    <p class="text-lg font-bold text-slate-800 font-mono">{{ $document->reference }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Code de vérification</p>
                    <p class="text-lg font-bold text-orange-600 font-mono">{{ $shortCode }}</p>
                </div>
            </div>

            <dl class="mt-5 divide-y divide-slate-100 text-sm">
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Titre</dt>
                    <dd class="text-slate-800 font-medium text-right">
                        @if($masked)
                            <span class="text-slate-400 italic"><i class="fas fa-lock mr-1"></i>Masqué (document confidentiel)</span>
                        @else
                            {{ $document->title }}
                        @endif
                    </dd>
                </div>
                @unless($masked)
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-slate-500">Rédigé par</dt>
                        <dd class="text-slate-800 text-right">{{ $document->creator?->full_name ?? '—' }}</dd>
                    </div>
                    @if($document->category)
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-slate-500">Catégorie</dt>
                            <dd class="text-slate-800 text-right">{{ $document->category->name }}</dd>
                        </div>
                    @endif
                @endunless
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500">Date d'émission</dt>
                    <dd class="text-slate-800 text-right">{{ $document->created_at->format('d/m/Y') }}</dd>
                </div>
            </dl>
        </section>

        {{-- Conseils anti-fraude --}}
        <section class="rounded-2xl border border-slate-200 bg-white/60 p-6 text-sm text-slate-600">
            <h2 class="font-semibold text-slate-800 mb-3"><i class="fas fa-user-shield text-slate-500 mr-2"></i>Pour une vérification fiable</h2>
            <ul class="space-y-2">
                <li><i class="fas fa-check text-emerald-500 mr-2"></i>L'adresse dans votre navigateur doit commencer par <strong class="font-mono">{{ request()->getSchemeAndHttpHost() }}</strong>. Un QR code menant ailleurs est une contrefaçon.</li>
                <li><i class="fas fa-check text-emerald-500 mr-2"></i>La référence et le code imprimés en pied de page doivent être identiques à ceux affichés ici.</li>
                <li><i class="fas fa-check text-emerald-500 mr-2"></i>Sans QR code lisible, saisissez le code sur <a href="{{ route('verification.lookup') }}" class="text-orange-600 font-medium hover:underline">{{ request()->getHost() }}/verify</a>.</li>
            </ul>
        </section>

        <p class="text-center text-xs text-slate-400">© {{ date('Y') }} {{ $orgName }} — Système de vérification documentaire</p>
    </main>
</div>

@endsection
