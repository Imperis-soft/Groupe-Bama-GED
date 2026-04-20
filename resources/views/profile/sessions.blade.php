@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ revokeId: null, confirmOpen: false }">

    {{-- BREADCRUMB + HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[11px] text-slate-400 font-semibold mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-orange-500 transition-colors">
                    <i class="fa-solid fa-house text-[10px]"></i>
                </a>
                <i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>
                <a href="{{ route('profile.show') }}" class="hover:text-orange-500 transition-colors">Mon profil</a>
                <i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>
                <span class="text-slate-600">Sessions actives</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Sessions actives</h1>
            <p class="text-xs text-slate-400 font-medium mt-1.5">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                    {{ $sessions->count() }} session(s) ouverte(s) sur vos appareils
                </span>
            </p>
        </div>
        <a href="{{ route('profile.show') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour au profil
        </a>
    </div>

    {{-- ALERTE SÉCURITÉ --}}
    <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4">
        <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
            <i class="fa-solid fa-shield-exclamation text-amber-600 text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-black text-amber-900">Sécurité de votre compte</p>
            <p class="text-xs text-amber-700 font-medium mt-0.5 leading-relaxed">
                Si vous reconnaissez une session suspecte, révoquez-la immédiatement puis
                <a href="{{ route('profile.show') }}" class="font-black underline hover:text-amber-900">changez votre mot de passe</a>.
            </p>
        </div>
    </div>

    {{-- SESSIONS --}}
    @if($sessions->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-16 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
            <i class="fa-solid fa-desktop text-slate-300 text-2xl"></i>
        </div>
        <p class="text-sm font-bold text-slate-400">Aucune session active</p>
        <p class="text-xs text-slate-300 mt-1">Vos sessions apparaîtront ici</p>
    </div>
    @else

    <div class="space-y-3">
        @foreach($sessions as $session)
        @php
            $ua      = strtolower($session->user_agent ?? '');
            $isMobile = str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone');
            $isTablet = str_contains($ua, 'tablet') || str_contains($ua, 'ipad');
            $icon    = $isMobile ? 'fa-mobile-screen-button' : ($isTablet ? 'fa-tablet-screen-button' : 'fa-desktop');

            // Détecter le navigateur
            $browser = 'Navigateur inconnu';
            if (str_contains($ua, 'chrome') && !str_contains($ua, 'edg'))  $browser = 'Google Chrome';
            elseif (str_contains($ua, 'firefox'))  $browser = 'Mozilla Firefox';
            elseif (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) $browser = 'Safari';
            elseif (str_contains($ua, 'edg'))      $browser = 'Microsoft Edge';
            elseif (str_contains($ua, 'opera'))    $browser = 'Opera';

            // Détecter l'OS
            $os = 'Système inconnu';
            if (str_contains($ua, 'windows'))      $os = 'Windows';
            elseif (str_contains($ua, 'mac'))       $os = 'macOS';
            elseif (str_contains($ua, 'linux'))     $os = 'Linux';
            elseif (str_contains($ua, 'android'))   $os = 'Android';
            elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) $os = 'iOS';

            $deviceType = $isMobile ? 'Mobile' : ($isTablet ? 'Tablette' : 'Ordinateur');
        @endphp

        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden transition-all
            {{ $session->is_current
                ? 'border-green-200 shadow-green-50'
                : 'border-slate-100 hover:border-slate-200 hover:shadow-md' }}">

            {{-- Bande couleur top --}}
            <div class="h-0.5 w-full {{ $session->is_current ? 'bg-gradient-to-r from-green-400 to-emerald-500' : 'bg-slate-100' }}"></div>

            <div class="p-5">
                <div class="flex items-start gap-4">

                    {{-- Icône appareil --}}
                    <div class="relative shrink-0">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center
                            {{ $session->is_current ? 'bg-green-100' : 'bg-slate-100' }}">
                            <i class="fa-solid {{ $icon }} text-lg
                                {{ $session->is_current ? 'text-green-600' : 'text-slate-400' }}"></i>
                        </div>
                        @if($session->is_current)
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white flex items-center justify-center">
                            <i class="fa-solid fa-check text-white text-[6px]"></i>
                        </span>
                        @endif
                    </div>

                    {{-- Infos --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <p class="text-sm font-black text-slate-900">{{ $browser }}</p>
                            @if($session->is_current)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 text-[9px] font-black uppercase tracking-wider rounded-lg">
                                <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse"></span>
                                Session actuelle
                            </span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1">
                            <span class="flex items-center gap-1.5 text-[10px] text-slate-500 font-medium">
                                <i class="fa-solid fa-{{ $isMobile ? 'mobile' : ($isTablet ? 'tablet' : 'computer') }} text-slate-300 text-[9px]"></i>
                                {{ $deviceType }} · {{ $os }}
                            </span>
                            <span class="flex items-center gap-1.5 text-[10px] text-slate-500 font-medium">
                                <i class="fa-solid fa-location-dot text-slate-300 text-[9px]"></i>
                                {{ $session->ip_address ?? 'IP inconnue' }}
                            </span>
                            <span class="flex items-center gap-1.5 text-[10px] text-slate-500 font-medium">
                                <i class="fa-regular fa-clock text-slate-300 text-[9px]"></i>
                                {{ $session->last_activity->diffForHumans() }}
                            </span>
                        </div>

                        {{-- User agent complet (collapsible) --}}
                        <div x-data="{ expanded: false }" class="mt-2">
                            <button @click="expanded = !expanded"
                                class="text-[9px] font-bold text-slate-300 hover:text-slate-500 transition-colors flex items-center gap-1">
                                <i class="fa-solid fa-code text-[8px]"></i>
                                <span x-text="expanded ? 'Masquer les détails' : 'Voir les détails techniques'"></span>
                            </button>
                            <div x-show="expanded" x-transition class="mt-1.5">
                                <p class="text-[9px] text-slate-400 font-mono bg-slate-50 rounded-lg px-3 py-2 break-all leading-relaxed border border-slate-100">
                                    {{ $session->user_agent ?? 'User-agent non disponible' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Action --}}
                    <div class="shrink-0">
                        @if($session->is_current)
                        <div class="flex items-center gap-1.5 px-3 py-2 bg-green-50 border border-green-100 rounded-xl">
                            <i class="fa-solid fa-shield-check text-green-500 text-xs"></i>
                            <span class="text-[9px] font-black text-green-700 uppercase tracking-wider">Actif</span>
                        </div>
                        @else
                        <button
                            @click="revokeId = '{{ $session->id }}'; confirmOpen = true"
                            class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 border border-red-100 hover:border-red-200 text-red-600 text-[10px] font-black uppercase tracking-wider px-3 py-2 rounded-xl transition-all active:scale-95">
                            <i class="fa-solid fa-xmark text-[9px]"></i>
                            Révoquer
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Résumé --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm text-center">
            <p class="text-2xl font-black text-slate-900">{{ $sessions->count() }}</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mt-1">Sessions totales</p>
        </div>
        <div class="bg-white border border-green-100 rounded-2xl p-4 shadow-sm text-center">
            <p class="text-2xl font-black text-green-600">1</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mt-1">Session actuelle</p>
        </div>
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm text-center">
            <p class="text-2xl font-black text-slate-900">{{ $sessions->count() - 1 }}</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mt-1">Autres appareils</p>
        </div>
    </div>

    @endif

    {{-- Liens rapides --}}
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-5">
        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Actions de sécurité</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <a href="{{ route('profile.show') }}#password"
               class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 hover:bg-orange-50 hover:text-orange-700 text-slate-600 text-xs font-bold transition-all">
                <i class="fa-solid fa-key text-orange-400 text-sm"></i>
                Changer mon mot de passe
            </a>
            <a href="{{ route('profile.activity') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-600 text-xs font-bold transition-all">
                <i class="fa-solid fa-clock-rotate-left text-blue-400 text-sm"></i>
                Voir mon activité
            </a>
        </div>
    </div>

    {{-- MODAL CONFIRMATION RÉVOCATION --}}
    <div x-show="confirmOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div @click="confirmOpen = false; revokeId = null"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

        <div @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full">

            <div class="flex items-start gap-4 mb-5">
                <div class="w-11 h-11 rounded-2xl bg-red-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-shield-xmark text-red-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900">Révoquer cette session ?</p>
                    <p class="text-xs text-slate-400 font-medium mt-1 leading-relaxed">
                        L'appareil sera déconnecté immédiatement. Cette action est irréversible.
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <button @click="confirmOpen = false; revokeId = null"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                    Annuler
                </button>
                <form :action="'/profile/sessions/' + revokeId" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-500 active:scale-95 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-red-200 transition-all">
                        <i class="fa-solid fa-xmark mr-1.5 text-[10px]"></i>Révoquer
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
