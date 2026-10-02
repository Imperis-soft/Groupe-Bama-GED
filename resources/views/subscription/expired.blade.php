<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès suspendu — {{ config('saas.platform_name') }}</title>
    <x-favicons />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }</style>
</head>
<body class="min-h-full bg-gradient-to-br from-slate-50 via-orange-50/40 to-slate-100 antialiased flex items-center justify-center p-4">
@php
    $suspended = $organization?->isSuspended();
    $subject   = rawurlencode('Renouvellement abonnement — ' . ($organization?->name ?? ''));
@endphp

<div class="w-full max-w-lg">
<x-logo class="w-14 h-14 mx-auto mb-6" />
<div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
    <div class="px-8 pt-10 pb-6 text-center">
        <div class="w-16 h-16 mx-auto rounded-2xl {{ $suspended ? 'bg-red-50 text-red-600' : 'bg-orange-50 text-orange-600' }} flex items-center justify-center mb-5">
            <i class="fa-solid {{ $suspended ? 'fa-ban' : 'fa-hourglass-end' }} text-2xl"></i>
        </div>
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $organization?->name }}</p>
        <h1 class="text-xl font-black text-slate-900 mt-2">
            {{ $suspended ? 'Accès suspendu' : 'Abonnement terminé' }}
        </h1>
        <p class="text-sm text-slate-500 mt-3 leading-relaxed">
            @if($suspended)
                L'accès de votre entreprise à {{ config('saas.platform_name') }} est suspendu.
                @if($organization->suspended_reason)<br><span class="font-semibold text-slate-700">Motif : {{ $organization->suspended_reason }}</span>@endif
            @elseif($subscription)
                L'abonnement {{ $subscription->plan->name }} de votre entreprise a pris fin le
                <span class="font-semibold text-slate-700">{{ $subscription->ends_at->format('d/m/Y') }}</span>.
            @else
                Votre entreprise n'a pas d'abonnement en cours.
            @endif
        </p>
        <p class="text-sm text-slate-500 mt-3">
            Vos documents sont conservés en sécurité.
            {{ $isAdmin ? 'Contactez-nous pour renouveler et retrouver l\'accès immédiatement.' : 'Prévenez l\'administrateur de votre entreprise.' }}
        </p>
    </div>

    <div class="px-8 pb-8 space-y-3">
        <a href="mailto:{{ config('saas.support_email') }}?subject={{ $subject }}"
           class="flex items-center gap-3 p-4 rounded-2xl border border-slate-100 hover:border-orange-200 hover:bg-orange-50/50 transition">
            <span class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-envelope"></i></span>
            <span>
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Email</span>
                <span class="block text-sm font-black text-slate-900">{{ config('saas.support_email') }}</span>
            </span>
        </a>
        <a href="tel:{{ preg_replace('/\s+/', '', config('saas.support_phone')) }}"
           class="flex items-center gap-3 p-4 rounded-2xl border border-slate-100 hover:border-orange-200 hover:bg-orange-50/50 transition">
            <span class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-phone"></i></span>
            <span>
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Téléphone</span>
                <span class="block text-sm font-black text-slate-900">{{ config('saas.support_phone') }}</span>
            </span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="pt-3 text-center">
            @csrf
            <button class="text-xs font-bold text-slate-400 hover:text-slate-700"><i class="fa-solid fa-right-from-bracket"></i> Se déconnecter</button>
        </form>
    </div>

    <div class="px-8 py-4 bg-slate-50 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
        {{ config('saas.platform_name') }} · {{ config('saas.vendor_name') }}
    </div>
</div>
</div>
</body>
</html>
