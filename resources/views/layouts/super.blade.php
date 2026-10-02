<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super admin') — {{ config('saas.platform_name') }}</title>
    <x-favicons />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: { ink: '#0b1220' },
                    boxShadow: {
                        soft: '0 1px 2px rgba(15,23,42,.04), 0 2px 6px -2px rgba(15,23,42,.04)',
                        lift: '0 1px 2px rgba(15,23,42,.04), 0 12px 32px -12px rgba(15,23,42,.14)',
                        pop:  '0 24px 48px -12px rgba(15,23,42,.18), 0 4px 10px -4px rgba(15,23,42,.06)',
                        cta:  '0 8px 20px -6px rgba(234,88,12,.45)',
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style type="text/tailwindcss">
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }
        .dots      { background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,.08) 1px, transparent 0); background-size: 26px 26px; }
        .dots-dark { background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0); background-size: 22px 22px; }
        .accent    { background: linear-gradient(120deg, #ea580c 0%, #f97316 55%, #fb923c 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .fade-b    { mask-image: linear-gradient(to bottom, #000 30%, transparent); -webkit-mask-image: linear-gradient(to bottom, #000 30%, transparent); }

        @layer components {
        /* En-têtes de page */
        .page-header   { @apply flex flex-wrap items-end justify-between gap-6; }
        .eyebrow       { @apply inline-flex items-center gap-2 text-[13px] font-semibold text-orange-600; }
        .page-title    { @apply text-[30px] sm:text-[34px] leading-[1.1] font-extrabold tracking-[-0.03em] text-slate-900; }
        .page-subtitle { @apply text-[15px] text-slate-500 mt-2 max-w-2xl; }
        .back-link     { @apply inline-flex items-center gap-2 text-[13px] font-semibold text-slate-500 hover:text-orange-600 transition mb-5; }

        /* Cartes */
        .card          { @apply bg-white rounded-[20px] border border-slate-200/70 shadow-soft; }
        .card-hover    { @apply transition duration-200 hover:-translate-y-0.5 hover:shadow-lift hover:border-slate-300/70; }
        .card-header   { @apply flex flex-wrap items-center justify-between gap-3 px-6 pt-5 pb-4; }
        .card-title    { @apply text-[16px] font-bold tracking-tight text-slate-900; }
        .card-subtitle { @apply text-[13px] text-slate-500 mt-0.5; }
        .card-body     { @apply px-6 pb-6; }
        .card-ink      { @apply relative overflow-hidden rounded-[20px] bg-ink text-white shadow-pop; }
        .section-icon  { @apply w-10 h-10 rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100 flex items-center justify-center shrink-0; }
        .kicker      { @apply text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400; }

        /* Formulaires */
        .label { @apply block text-[13px] font-semibold text-slate-700 mb-2; }
        .hint  { @apply text-xs text-slate-500 mt-2; }
        .field { @apply block w-full h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-900 placeholder:text-slate-400 transition hover:border-slate-300 focus:outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-500/10; }
        textarea.field { @apply h-auto py-3 leading-relaxed; }
        select.field {
            @apply appearance-none pr-10 cursor-pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right .75rem center; background-repeat: no-repeat; background-size: 1.25rem;
        }
        .field-sm { @apply h-9 text-[13px] px-3 rounded-lg; }
        .check { @apply w-4 h-4 rounded border-slate-300 accent-orange-600 cursor-pointer; }

        /* Boutons */
        .btn         { @apply inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl text-sm font-bold whitespace-nowrap transition focus:outline-none focus-visible:ring-4 disabled:opacity-50; }
        .btn i       { @apply text-[12px]; }
        .btn-sm      { @apply h-9 px-3.5 text-[13px] rounded-lg; }
        .btn-icon    { @apply w-11 px-0; }
        .btn-sm.btn-icon { @apply w-9; }
        .btn-primary { @apply bg-orange-600 text-white shadow-cta hover:bg-orange-700 hover:-translate-y-px focus-visible:ring-orange-500/25; }
        .btn-dark    { @apply bg-ink text-white hover:bg-slate-800 focus-visible:ring-slate-400/30; }
        .btn-light   { @apply bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 focus-visible:ring-slate-200; }
        .btn-ghost   { @apply text-slate-600 hover:bg-slate-100 hover:text-slate-900; }
        .btn-danger  { @apply bg-white text-red-600 border border-red-200 hover:bg-red-50 focus-visible:ring-red-500/15; }
        .btn-danger-solid { @apply bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500/25; }
        .link        { @apply font-semibold text-orange-600 hover:text-orange-700; }

        /* Badges */
        .badge        { @apply inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold leading-none whitespace-nowrap; }
        .badge .dot   { @apply w-1.5 h-1.5 rounded-full bg-current; }
        .badge-green  { @apply bg-emerald-50 text-emerald-700; }
        .badge-orange { @apply bg-orange-50 text-orange-700; }
        .badge-red    { @apply bg-red-50 text-red-700; }
        .badge-amber  { @apply bg-amber-50 text-amber-700; }
        .badge-blue   { @apply bg-sky-50 text-sky-700; }
        .badge-violet { @apply bg-violet-50 text-violet-700; }
        .badge-slate  { @apply bg-slate-100 text-slate-600; }
        .badge-dark   { @apply bg-ink text-white; }

        /* Tableaux */
        table.data           { @apply w-full; }
        table.data th        { @apply px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.1em] text-slate-400 border-y border-slate-100 bg-slate-50/60 whitespace-nowrap; }
        table.data td        { @apply px-6 py-4 text-sm text-slate-600 border-b border-slate-100 align-middle; }
        table.data tbody tr  { @apply transition-colors hover:bg-orange-50/30; }
        table.data tbody tr:last-child td { @apply border-b-0; }

        /* Divers */
        .avatar    { @apply inline-flex items-center justify-center shrink-0 rounded-xl bg-gradient-to-br from-orange-50 to-orange-100 text-orange-700 font-bold ring-1 ring-orange-200/60 w-10 h-10 text-xs; }
        .avatar-lg { @apply w-16 h-16 text-xl rounded-2xl; }
        .avatar-sm { @apply w-8 h-8 text-[10px] rounded-lg; }
        .avatar-round { @apply rounded-full; }
        .meter     { @apply h-2 w-full rounded-full bg-slate-100 overflow-hidden; }
        .meter > span { @apply block h-full rounded-full bg-gradient-to-r from-orange-400 to-orange-600; }
        .empty     { @apply flex flex-col items-center justify-center text-center px-6 py-14; }
        .empty-icon { @apply w-14 h-14 rounded-2xl bg-white shadow-soft ring-1 ring-slate-200/70 text-slate-400 flex items-center justify-center mb-4 text-lg; }
        .filters   { @apply flex flex-wrap items-center gap-3 mb-6; }
        .tabs      { @apply inline-flex flex-wrap gap-1 rounded-2xl bg-white border border-slate-200/70 p-1.5 shadow-soft; }
        .tab       { @apply inline-flex items-center gap-2 h-9 px-4 rounded-xl text-[13px] font-semibold text-slate-500 hover:text-slate-900 transition; }
        .tab.active { @apply bg-ink text-white; }
        .tab .count { @apply text-[11px] font-bold tabular-nums opacity-60; }
        .tab.active .count { @apply text-orange-300 opacity-100; }
        .kv        { @apply flex items-center justify-between gap-4 py-3 text-sm border-b border-slate-100 last:border-0; }
        .kv dt     { @apply text-slate-500; }
        .kv dd     { @apply font-semibold text-slate-900 text-right; }
        .dropdown  { @apply absolute z-30 mt-2 rounded-2xl border border-slate-200/80 bg-white shadow-pop; }
        .stat      { @apply text-[30px] leading-none font-extrabold tracking-[-0.03em] text-slate-900 tabular-nums; }

        nav[role="navigation"] { @apply mt-6; }
        }
    </style>
</head>
<body class="h-full bg-[#fafafb] font-sans text-slate-700 antialiased" x-data="{ sidebarOpen: false }">

<x-page-loader />

@php
    $navGroups = [
        'Pilotage' => [
            ['super.dashboard', 'super.dashboard', 'fa-chart-pie', 'Tableau de bord'],
            ['super.organizations.index', 'super.organizations.*', 'fa-building', 'Entreprises'],
            ['super.subscriptions.index', 'super.subscriptions.*', 'fa-receipt', 'Abonnements'],
        ],
        'Commercial' => [
            ['super.demo-requests.index', 'super.demo-requests.*', 'fa-inbox', 'Demandes de démo'],
            ['super.plans.index', 'super.plans.*', 'fa-layer-group', 'Offres'],
        ],
        'Administration' => [
            ['super.users.index', 'super.users.*', 'fa-user-group', 'Utilisateurs'],
            ['super.departments.index', 'super.departments.*', 'fa-sitemap', 'Services'],
            ['super.activity.index', 'super.activity.*', 'fa-clock-rotate-left', 'Journal'],
            ['super.system.index', 'super.system.*', 'fa-heart-pulse', 'Système'],
            ['super.settings.index', 'super.settings.*', 'fa-gear', 'Paramètres'],
        ],
    ];
    $newDemoRequests = \App\Models\DemoRequest::where('status', 'new')->count();
    $orgTotal = \App\Models\Organization::count();
    $me = auth()->user();
@endphp

<div class="flex h-full">
    <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-ink/40 backdrop-blur-sm lg:hidden"></div>

    {{-- ======= SIDEBAR ======= --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-[272px] flex flex-col bg-white border-r border-slate-200/70 transition-transform duration-300 lg:static lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0 shadow-pop' : '-translate-x-full'">
        <div class="h-[72px] shrink-0 flex items-center gap-3 px-6">
            <x-logo class="w-11 h-11" />
            <div class="min-w-0">
                <p class="text-[17px] font-extrabold tracking-tight text-slate-900 leading-none">{{ config('saas.platform_name') }}</p>
                <p class="text-[11px] font-medium text-slate-500 mt-1">Console {{ config('saas.vendor_name') }}</p>
            </div>
            <button @click="sidebarOpen = false" class="ml-auto btn btn-ghost btn-sm btn-icon lg:hidden"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <nav class="flex-1 min-h-0 overflow-y-auto px-4 pt-3 pb-6 space-y-7">
            @foreach($navGroups as $group => $items)
                <div>
                    <p class="kicker px-3 mb-2.5">{{ $group }}</p>
                    <div class="space-y-1">
                        @foreach($items as [$route, $pattern, $icon, $label])
                            @php($active = request()->routeIs($pattern))
                            <a href="{{ route($route) }}"
                               class="group flex items-center gap-3 h-11 px-3 rounded-xl text-[14px] font-semibold transition
                                      {{ $active ? 'bg-orange-600 text-white shadow-cta' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center {{ $active ? 'bg-white/15' : 'bg-slate-50 group-hover:bg-white group-hover:shadow-soft' }}">
                                    <i class="fa-solid {{ $icon }} text-[12px] {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-orange-600' }}"></i>
                                </span>
                                {{ $label }}
                                @if($route === 'super.system.index' && ($systemProblems = \App\Models\SystemEvent::open()->whereIn('level', ['critical', 'error'])->count()) > 0)
                                    <span class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center tabular-nums {{ $active ? 'bg-white text-red-600' : 'bg-red-100 text-red-700' }}">{{ $systemProblems }}</span>
                                @endif
                                @if($route === 'super.demo-requests.index' && $newDemoRequests > 0)
                                    <span class="ml-auto min-w-[22px] h-[22px] px-1.5 rounded-full text-[11px] font-bold flex items-center justify-center tabular-nums {{ $active ? 'bg-white text-orange-600' : 'bg-orange-100 text-orange-700' }}">{{ $newDemoRequests }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Carte d'état (masquée sur les écrans bas pour laisser le menu visible) --}}
        <div class="px-4 pb-3 [@media(max-height:860px)]:hidden">
            <div class="card-ink p-4">
                <div class="absolute inset-0 dots-dark"></div>
                <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-orange-500/30 blur-2xl"></div>
                <div class="relative">
                    <p class="flex items-center gap-2 text-[11px] font-semibold text-emerald-400">
                        <span class="relative flex w-2 h-2"><span class="absolute inline-flex w-full h-full rounded-full bg-emerald-400 opacity-60 animate-ping"></span><span class="relative w-2 h-2 rounded-full bg-emerald-400"></span></span>
                        Plateforme en ligne
                    </p>
                    <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums">{{ $orgTotal }}</p>
                    <p class="text-xs text-slate-400">entreprise(s) hébergée(s)</p>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100">
            <div class="flex items-center gap-3">
                <span class="avatar avatar-round">{{ initials($me->full_name) }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-900 truncate">{{ $me->full_name }}</p>
                    <p class="text-xs text-slate-500 truncate">Super administrateur</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-ghost btn-sm btn-icon" title="Déconnexion"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ======= CONTENU ======= --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="sticky top-0 z-30 h-[72px] shrink-0 flex items-center gap-4 px-4 sm:px-8 bg-white/80 backdrop-blur-xl border-b border-slate-200/70">
            <button @click="sidebarOpen = true" class="btn btn-light btn-icon lg:hidden"><i class="fa-solid fa-bars"></i></button>

            <form method="GET" action="{{ route('super.organizations.index') }}" class="relative flex-1 max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-[13px] text-slate-400"></i>
                <input type="search" name="q" value="{{ request()->routeIs('super.organizations.index') ? request('q') : '' }}"
                       placeholder="Rechercher une entreprise…" class="field !pl-11 !bg-slate-50 !border-transparent focus:!bg-white focus:!border-orange-400">
            </form>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('super.demo-requests.index', ['status' => 'new']) }}" class="relative btn btn-light btn-icon" title="Nouvelles demandes de démo">
                    <i class="fa-regular fa-bell !text-[15px]"></i>
                    @if($newDemoRequests > 0)
                        <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-orange-600 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white">{{ $newDemoRequests }}</span>
                    @endif
                </a>
                @unless(request()->routeIs('super.organizations.*'))
                <a href="{{ route('super.organizations.create') }}" class="btn btn-primary hidden sm:inline-flex">
                    <i class="fa-solid fa-plus"></i> Nouvelle entreprise
                </a>
                @endunless
            </div>
        </header>

        <main class="flex-1 overflow-y-auto">
            {{-- Bandeau d'en-tête : motif de points + halo orange, comme le site --}}
            @hasSection('header')
                <div class="relative overflow-hidden bg-white border-b border-slate-200/70">
                    <div class="absolute inset-0 dots fade-b"></div>
                    <div class="absolute -top-32 right-0 w-[520px] h-[320px] rounded-full bg-orange-400/20 blur-3xl"></div>
                    <div class="relative max-w-7xl mx-auto px-4 sm:px-8 pt-9 pb-10">
                        @yield('header')
                    </div>
                </div>
            @endif

            <div class="max-w-7xl mx-auto px-4 sm:px-8 py-8">
                @if($errors->any())
                    <div class="mb-6 flex gap-3 rounded-2xl border border-red-200 bg-red-50/70 px-5 py-4">
                        <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-bold text-red-800">Veuillez corriger les erreurs suivantes</p>
                            <ul class="mt-1 list-disc pl-5 text-sm text-red-700 space-y-0.5">
                                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</div>

{{-- Notifications flash --}}
@foreach(['success' => ['fa-circle-check', 'text-emerald-400'], 'error' => ['fa-circle-exclamation', 'text-red-400']] as $type => [$icon, $color])
    @if(session($type))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0"
             class="fixed bottom-6 right-6 z-[60] w-[calc(100%-3rem)] max-w-sm flex items-start gap-3 rounded-2xl bg-ink p-4 text-white shadow-pop">
            <i class="fa-solid {{ $icon }} {{ $color }} text-lg"></i>
            <p class="flex-1 text-sm pt-0.5">{{ session($type) }}</p>
            <button @click="show = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif
@endforeach
</body>
</html>
