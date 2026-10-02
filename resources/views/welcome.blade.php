<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('saas.platform_name') }} — Gestion électronique de documents pour les entreprises | {{ config('saas.vendor_name') }}</title>
    <x-favicons />
    <meta name="description" content="{{ config('saas.platform_name') }} centralise, valide, signe et archive les documents de votre entreprise. En SaaS ou installée chez vous (SingleEntity). Une solution {{ config('saas.vendor_name') }}.">
    <meta property="og:image" content="{{ asset('images/logo-ged.png') }}">
    <meta property="og:title" content="{{ config('saas.platform_name') }} — La gestion documentaire des entreprises exigeantes">
    <meta property="og:description" content="Versions, workflow d'approbation, signature, QR code d'authenticité, archivage et traçabilité complète. En SaaS ou en installation dédiée.">
    <script>document.documentElement.classList.add('js')</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'], mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'] },
                colors: { ink: '#0b1220' },
            } },
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    {{-- Le plugin collapse doit être chargé avant Alpine --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::selection { background: #fed7aa; color: #0b1220; }
        .dots      { background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,.08) 1px, transparent 0); background-size: 28px 28px; }
        .dots-dark { background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0); background-size: 26px 26px; }
        .accent    { background: linear-gradient(120deg, #ea580c 0%, #f97316 50%, #fb923c 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .field { width: 100%; background: #fff; border: 1px solid #e2e8f0; border-radius: .875rem; padding: .8rem 1rem; font-size: .875rem; color: #0f172a; transition: border-color .15s, box-shadow .15s; }
        .field:hover { border-color: #cbd5e1; }
        .field:focus { outline: none; border-color: #fb923c; box-shadow: 0 0 0 4px rgba(249,115,22,.12); }
        .field-label { display: block; font-size: .75rem; font-weight: 700; color: #334155; margin-bottom: .45rem; }
        .eyebrow { display: inline-flex; align-items: center; gap: .5rem; font-size: .8125rem; font-weight: 700; color: #ea580c; }
        .eyebrow::before { content: ''; width: 18px; height: 2px; border-radius: 2px; background: currentColor; }

        /* ---------- Apparition au défilement ---------- */
        .js .reveal { opacity: 0; transform: translateY(28px); transition: opacity .9s cubic-bezier(.22,1,.36,1), transform .9s cubic-bezier(.22,1,.36,1); transition-delay: var(--d, 0ms); }
        .js .reveal.is-visible { opacity: 1; transform: none; }

        /* ---------- Hero ---------- */
        .word { display: inline-block; overflow: hidden; vertical-align: bottom; padding-bottom: .08em; margin-bottom: -.08em; }
        .word > span { display: inline-block; transform: translateY(105%); animation: word-up 1s cubic-bezier(.22,1,.36,1) forwards; animation-delay: calc(var(--i) * 70ms + 120ms); }
        @keyframes word-up { to { transform: none; } }
        .hero-in { opacity: 0; transform: translateY(18px); animation: fade-up 1s cubic-bezier(.22,1,.36,1) forwards; animation-delay: var(--d, 0ms); }
        @keyframes fade-up { to { opacity: 1; transform: none; } }
        .spotlight { background: radial-gradient(650px circle at var(--mx, 70%) var(--my, 30%), rgba(249,115,22,.13), transparent 60%); }
        .blob { position: absolute; border-radius: 9999px; filter: blur(70px); animation: drift 18s ease-in-out infinite alternate; }
        @keyframes drift { 0% { transform: translate(0,0) scale(1); } 50% { transform: translate(-60px, 40px) scale(1.12); } 100% { transform: translate(40px, -30px) scale(.95); } }
        .float   { animation: float 6s ease-in-out infinite; }
        .float-2 { animation: float 7s ease-in-out infinite; animation-delay: -3s; }
        @keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        #tilt { transition: transform .4s cubic-bezier(.22,1,.36,1); transform-style: preserve-3d; will-change: transform; }

        /* ---------- Bandeau défilant ---------- */
        .marquee { mask-image: linear-gradient(90deg, transparent, #000 12%, #000 88%, transparent); -webkit-mask-image: linear-gradient(90deg, transparent, #000 12%, #000 88%, transparent); }
        .marquee-track { display: flex; width: max-content; animation: marquee 38s linear infinite; }
        .marquee:hover .marquee-track { animation-play-state: paused; }
        @keyframes marquee { to { transform: translateX(-50%); } }

        /* ---------- Barré animé (section problème) ---------- */
        .strike { position: relative; }
        .strike::after { content: ''; position: absolute; left: 0; right: 0; top: 55%; height: 2px; background: #f87171; border-radius: 2px; transform: scaleX(0); transform-origin: left; transition: transform .7s cubic-bezier(.65,0,.35,1); transition-delay: calc(var(--d, 0ms) + 500ms); }
        .is-visible .strike::after { transform: scaleX(1); }

        /* ---------- Cartes à halo ---------- */
        .spot { position: relative; isolation: isolate; }
        .spot::before { content: ''; position: absolute; inset: 0; border-radius: inherit; z-index: -1; opacity: 0; transition: opacity .3s;
                        background: radial-gradient(420px circle at var(--x, 50%) var(--y, 50%), rgba(249,115,22,.10), transparent 45%); }
        .spot:hover::before { opacity: 1; }
        .spot-dark::before { background: radial-gradient(380px circle at var(--x, 50%) var(--y, 50%), rgba(249,115,22,.18), transparent 45%); }

        /* ---------- Illustrations des fonctionnalités ---------- */
        .ver { position: absolute; transition: transform .6s cubic-bezier(.22,1,.36,1); }
        .ver-1 { animation: ver1 6s ease-in-out infinite; } .ver-2 { animation: ver2 6s ease-in-out infinite; } .ver-3 { animation: ver3 6s ease-in-out infinite; }
        @keyframes ver1 { 0%,100% { transform: translate(-34px, 14px) rotate(-6deg); } 50% { transform: translate(-58px, 18px) rotate(-10deg); } }
        @keyframes ver2 { 0%,100% { transform: translate(0, 4px) rotate(-1deg); } 50% { transform: translate(0, 0) rotate(0deg); } }
        @keyframes ver3 { 0%,100% { transform: translate(34px, -6px) rotate(5deg); } 50% { transform: translate(58px, -12px) rotate(9deg); } }
        .node { animation: node-on 4.5s infinite; animation-delay: calc(var(--n) * 1.1s); }
        @keyframes node-on { 0%, 8% { background: #f1f5f9; color: #94a3b8; box-shadow: none; } 18%, 82% { background: #10b981; color: #fff; box-shadow: 0 0 0 6px rgba(16,185,129,.15); } 100% { background: #f1f5f9; color: #94a3b8; } }
        .flow-line { transform-origin: left; animation: flow 4.5s infinite; }
        @keyframes flow { 0% { transform: scaleX(0); } 60%, 85% { transform: scaleX(1); } 100% { transform: scaleX(0); opacity: 0; } }
        .sig-path { stroke-dasharray: 640; stroke-dashoffset: 640; animation: draw 4.5s cubic-bezier(.65,0,.35,1) infinite; }
        @keyframes draw { 0% { stroke-dashoffset: 640; opacity: 1; } 55%, 85% { stroke-dashoffset: 0; opacity: 1; } 100% { stroke-dashoffset: 0; opacity: 0; } }
        .scan { animation: scan 2.6s ease-in-out infinite; }
        @keyframes scan { 0%, 100% { top: 4%; } 50% { top: 90%; } }
        .pop { animation: pop 2.6s ease-in-out infinite; }
        @keyframes pop { 0%, 40% { opacity: 0; transform: translateY(6px) scale(.9); } 55%, 90% { opacity: 1; transform: none; } 100% { opacity: 0; } }
        .caret { display: inline-block; width: 2px; height: 1.1em; background: #ea580c; vertical-align: -3px; margin-left: 1px; animation: blink 1s steps(1) infinite; }
        @keyframes blink { 50% { opacity: 0; } }

        /* ---------- Étapes liées au défilement ---------- */
        #steps .rail::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, #f97316, #ea580c); border-radius: 2px; transform: scaleX(var(--p, 0)); transform-origin: left; transition: transform .2s linear; }
        #steps [data-step] .badge-step { transition: all .5s cubic-bezier(.22,1,.36,1); }
        #steps [data-step].on .badge-step { background: #ea580c; color: #fff; transform: scale(1.06); box-shadow: 0 12px 28px -8px rgba(234,88,12,.55); }

        /* ---------- Orbite sécurité ---------- */
        .orbit { animation: spin 48s linear infinite; }
        .orbit-item > span { animation: counter-spin 48s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes counter-spin { from { transform: rotate(calc(-1 * var(--a))); } to { transform: rotate(calc(-1 * var(--a) - 360deg)); } }
        .pulse-ring { animation: pulse-ring 3s cubic-bezier(.22,1,.36,1) infinite; }
        @keyframes pulse-ring { 0% { transform: scale(.85); opacity: .6; } 100% { transform: scale(1.6); opacity: 0; } }

        /* ---------- Bordure lumineuse (SaaS) ---------- */
        @property --angle { syntax: '<angle>'; inherits: false; initial-value: 0deg; }
        .glow-border { padding: 2px; border-radius: 1.75rem; background: conic-gradient(from var(--angle), #fed7aa, #ea580c, #fb923c, #fff7ed, #fdba74, #fed7aa); animation: angle 6s linear infinite; }
        @keyframes angle { to { --angle: 360deg; } }

        /* ---------- Barre de lecture ---------- */
        #progress { transform-origin: left; transform: scaleX(0); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .js .reveal, .hero-in, .word > span { opacity: 1 !important; transform: none !important; }
            .sig-path { stroke-dashoffset: 0; }
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-900 bg-white overflow-x-hidden"
      x-data="{ mobileNav: false, formula: @js(old('formula', 'unsure')), scrolled: false,
                choose(f) { this.formula = f; document.getElementById('contact').scrollIntoView({ behavior: 'smooth' }); } }"
      @scroll.window="scrolled = window.scrollY > 12">

@php
    $platform = config('saas.platform_name');
    $vendor   = config('saas.vendor_name');
    $phoneTel = preg_replace('/\s+/', '', config('saas.support_phone'));
    $plans    = $plans ?? collect();
    $features = [
        ['fa-code-branch', 'Versions et historique', 'Chaque modification crée une version horodatée avec son empreinte SHA-256. Comparez, restaurez, ne perdez plus jamais la bonne version.'],
        ['fa-diagram-project', 'Workflow d\'approbation', 'Définissez les valideurs et l\'ordre des étapes. Chacun est notifié à son tour ; un rejet renvoie le document en brouillon avec le motif.'],
        ['fa-signature', 'Signature électronique', 'Signature manuscrite à l\'écran, liée au contenu exact du fichier : toute modification ultérieure est détectée.'],
        ['fa-qrcode', 'QR code d\'authenticité', 'Chaque document porte un QR code. Vos partenaires le scannent et vérifient en ligne qu\'il est authentique.'],
        ['fa-magnifying-glass', 'Recherche dans le contenu', 'Retrouvez un document par son titre, sa référence, ses tags ou les mots qu\'il contient, en quelques secondes.'],
        ['fa-share-nodes', 'Partage maîtrisé', 'Partagez en lecture, commentaire ou édition, avec date d\'expiration et révocation à tout moment.'],
        ['fa-box-archive', 'Archivage et rétention', 'Durées de conservation par catégorie, gel juridique (Legal Hold) et corbeille avant suppression définitive.'],
        ['fa-user-shield', 'Confidentialité', 'Documents confidentiels marqués d\'un filigrane au nom du lecteur à chaque téléchargement.'],
        ['fa-list-check', 'Traçabilité totale', 'Consultations, téléchargements, modifications, partages : tout est journalisé avec l\'auteur, la date et l\'adresse IP.'],
    ];
    $sectors = [
        ['fa-building-columns', 'Banques & microfinance'], ['fa-helmet-safety', 'BTP & industrie'], ['fa-landmark', 'Administrations'],
        ['fa-heart-pulse', 'Santé'], ['fa-hand-holding-heart', 'ONG & projets'], ['fa-scale-balanced', 'Cabinets & études'],
        ['fa-truck-fast', 'Logistique'], ['fa-graduation-cap', 'Éducation'],
    ];
    $navLinks = ['fonctionnalites' => 'Fonctionnalités', 'fonctionnement' => 'Fonctionnement', 'securite' => 'Sécurité', 'formules' => 'Formules', 'faq' => 'FAQ'];
    $word = 0; // index d'animation des mots du titre
@endphp

{{-- Barre de progression de lecture --}}
<div id="progress" class="fixed top-0 inset-x-0 h-[3px] z-[60] bg-gradient-to-r from-orange-400 via-orange-500 to-orange-600"></div>

{{-- ================= NAVIGATION ================= --}}
<header class="fixed inset-x-0 top-0 z-50 transition-all duration-500" :class="scrolled ? 'py-2' : 'py-4'">
    <nav class="mx-auto px-4 sm:px-6 transition-all duration-500" :class="scrolled ? 'max-w-6xl' : 'max-w-7xl'">
        <div class="h-14 flex items-center justify-between gap-6 rounded-2xl px-3 sm:px-4 transition-all duration-500"
             :class="scrolled ? 'bg-white/80 backdrop-blur-xl border border-slate-200/70 shadow-lg shadow-slate-900/5' : 'border border-transparent'">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 shrink-0 group">
                <x-logo class="w-10 h-10 transition group-hover:rotate-[-8deg] group-hover:scale-105" />
                <span class="leading-none">
                    <span class="block text-[17px] font-extrabold tracking-tight">{{ $platform }}</span>
                    <span class="block text-[10px] font-semibold text-slate-500 mt-0.5">par {{ $vendor }}</span>
                </span>
            </a>

            <div class="hidden lg:flex items-center gap-1 text-[13px] font-semibold text-slate-600">
                @foreach($navLinks as $anchor => $label)
                    <a href="#{{ $anchor }}" data-nav="{{ $anchor }}" class="relative px-3.5 py-2 rounded-xl hover:text-slate-900 hover:bg-slate-100/70 transition">{{ $label }}</a>
                @endforeach
            </div>

            <div class="hidden sm:flex items-center gap-2">
                <a href="{{ route('login') }}" class="px-4 py-2 text-[13px] font-bold text-slate-700 hover:text-slate-900">Se connecter</a>
                <a href="#contact" class="group inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white text-[13px] font-bold shadow-lg shadow-orange-600/25 transition">
                    Demander une démo <i class="fa-solid fa-arrow-right text-[10px] transition group-hover:translate-x-0.5"></i>
                </a>
            </div>

            <button @click="mobileNav = !mobileNav" class="sm:hidden w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700" aria-label="Menu">
                <i class="fa-solid" :class="mobileNav ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
        </div>

        <div x-show="mobileNav" x-cloak x-transition @click.outside="mobileNav = false" class="sm:hidden mt-2 rounded-2xl bg-white border border-slate-200 shadow-xl p-3">
            @foreach($navLinks as $anchor => $label)
                <a href="#{{ $anchor }}" @click="mobileNav = false" class="block px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $label }}</a>
            @endforeach
            <div class="grid grid-cols-2 gap-2 mt-2">
                <a href="{{ route('login') }}" class="text-center px-3 py-2.5 rounded-xl border border-slate-200 text-sm font-bold">Se connecter</a>
                <a href="#contact" @click="mobileNav = false" class="text-center px-3 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-bold">Démo</a>
            </div>
        </div>
    </nav>
</header>

<main>
{{-- ================= HERO ================= --}}
<section id="hero" class="relative pt-32 sm:pt-40 pb-24 overflow-hidden">
    <div class="absolute inset-0 dots [mask-image:radial-gradient(ellipse_at_top,black_35%,transparent_75%)]"></div>
    <div class="blob w-[520px] h-[520px] bg-orange-300/40 -top-40 right-[-8%]"></div>
    <div class="blob w-[380px] h-[380px] bg-amber-200/50 top-40 right-[25%]" style="animation-delay: -6s"></div>
    <div class="absolute inset-0 spotlight pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 grid lg:grid-cols-12 gap-14 lg:gap-8 items-center">
        <div class="lg:col-span-6">
            <a href="#formules" class="hero-in inline-flex items-center gap-2 rounded-full border border-orange-200 bg-white/80 backdrop-blur pl-1.5 pr-3.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:border-orange-300 hover:shadow-md transition">
                <span class="relative rounded-full bg-orange-600 text-white px-2 py-0.5 text-[10px] font-bold">
                    <span class="absolute inset-0 rounded-full bg-orange-500 pulse-ring"></span><span class="relative">Nouveau</span>
                </span>
                Disponible en SaaS et en installation dédiée
                <i class="fa-solid fa-arrow-right text-[10px] text-orange-600"></i>
            </a>

            <h1 class="mt-7 text-[2.7rem] leading-[1.04] sm:text-[4.2rem] font-extrabold tracking-[-0.035em]">
                @foreach(explode(' ', "Vos documents d'entreprise,") as $w)<span class="word"><span style="--i: {{ $word++ }}">{{ $w }}</span></span> @endforeach
                <br>
                <span class="word" x-data="{ words: ['maîtrisés', 'validés', 'signés', 'archivés', 'sécurisés'], i: 0 }"
                      x-init="if (!matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => i = (i + 1) % words.length, 2400)">
                    <span style="--i: {{ $word++ }}; display: inline-grid">
                        @foreach(['maîtrisés', 'validés', 'signés', 'archivés', 'sécurisés'] as $k => $rot)
                            <span style="grid-area: 1 / 1" class="accent transition-all duration-700 ease-[cubic-bezier(.22,1,.36,1)] {{ $k === 0 ? '' : 'opacity-0 translate-y-full' }}"
                                  :class="i === {{ $k }} ? '!opacity-100 !translate-y-0' : (i === ({{ $k }} + 1) % words.length ? 'opacity-0 !-translate-y-full' : 'opacity-0 translate-y-full')">{{ $rot }}</span>
                        @endforeach
                    </span>
                </span>
                <br>
                @foreach(explode(' ', 'de bout en bout.') as $w)<span class="word"><span style="--i: {{ $word++ }}">{{ $w }}</span></span> @endforeach
            </h1>

            <p class="hero-in mt-7 text-lg text-slate-600 leading-relaxed max-w-xl" style="--d: 650ms">
                {{ $platform }} centralise, fait valider, signe et archive tous vos documents dans un espace unique et sécurisé.
                Chaque version est conservée, chaque action est tracée, chaque document est vérifiable.
            </p>

            <div class="hero-in mt-9 flex flex-col sm:flex-row gap-3" style="--d: 800ms">
                <a href="#contact" class="group relative inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-orange-600 text-white font-bold shadow-xl shadow-orange-600/30 overflow-hidden transition hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-orange-600/40">
                    <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/25 to-transparent"></span>
                    <span class="relative">Demander une démo gratuite</span>
                    <i class="relative fa-solid fa-arrow-right text-xs transition group-hover:translate-x-1"></i>
                </a>
                <a href="#formules" class="inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-800 font-bold transition">
                    <i class="fa-regular fa-circle-play text-orange-600"></i> Voir les formules
                </a>
            </div>

            <ul class="hero-in mt-9 flex flex-wrap gap-x-6 gap-y-3 text-sm font-medium text-slate-600" style="--d: 950ms">
                <li class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-check text-[9px]"></i></span> Essai {{ config('saas.trial_days') }} jours</li>
                <li class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-check text-[9px]"></i></span> Paiement mobile money</li>
                <li class="flex items-center gap-2"><span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center"><i class="fa-solid fa-check text-[9px]"></i></span> Support local</li>
            </ul>
        </div>

        {{-- Aperçu produit animé --}}
        <div class="lg:col-span-6 hero-in" style="--d: 450ms" aria-hidden="true" x-data="heroDemo()">
            <div id="tilt" class="relative">
                <div class="absolute -inset-6 rounded-[2.5rem] bg-gradient-to-tr from-orange-500/25 via-orange-200/10 to-slate-900/10 blur-2xl"></div>

                <div class="relative rounded-2xl bg-white border border-slate-200/80 shadow-2xl shadow-slate-900/15 overflow-hidden">
                    <div class="flex items-center gap-2 px-4 h-10 border-b border-slate-100 bg-slate-50/80">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-300"></span><span class="w-2.5 h-2.5 rounded-full bg-amber-300"></span><span class="w-2.5 h-2.5 rounded-full bg-emerald-300"></span>
                        <span class="ml-3 flex-1 h-6 rounded-md bg-white border border-slate-200 text-[10px] text-slate-400 flex items-center px-2.5"><i class="fa-solid fa-lock text-[8px] mr-1.5 text-emerald-500"></i>ged · Documents</span>
                    </div>
                    <div class="grid grid-cols-12">
                        <div class="hidden sm:block col-span-3 border-r border-slate-100 p-3 space-y-1.5">
                            @foreach([['fa-gauge', 'Tableau de bord', false], ['fa-folder-open', 'Documents', true], ['fa-diagram-project', 'Approbations', false], ['fa-box-archive', 'Archives', false], ['fa-chart-simple', 'Rapports', false]] as [$i, $l, $on])
                                <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-semibold {{ $on ? 'bg-orange-600 text-white shadow-md shadow-orange-600/25' : 'text-slate-500' }}"><i class="fa-solid {{ $i }} w-3"></i>{{ $l }}</div>
                            @endforeach
                        </div>
                        <div class="col-span-12 sm:col-span-9 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-extrabold">Documents</p>
                                <span class="text-[10px] font-bold text-white bg-orange-600 rounded-md px-2 py-1"><i class="fa-solid fa-plus text-[8px] mr-1"></i>Nouveau</span>
                            </div>
                            {{-- Nouvelle ligne qui arrive --}}
                            <div x-show="phase === 3" x-collapse.duration.500ms>
                                <div class="flex items-center gap-3 py-2.5 px-2 -mx-2 rounded-lg bg-orange-50/70">
                                    <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shrink-0"><i class="fa-solid fa-file-word text-blue-500 text-sm"></i></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-bold truncate">Note de service — Octobre</p>
                                        <p class="text-[9px] text-slate-400 font-mono">ACME-N3W8QA · v1</p>
                                    </div>
                                    <span class="text-[9px] font-bold rounded-full px-2 py-0.5 bg-orange-100 text-orange-700">Nouveau</span>
                                </div>
                            </div>
                            @foreach([
                                ['Contrat cadre fournisseur', 'ACME-7K2Q9D', 'Approuvé', 'bg-emerald-50 text-emerald-700', 'v3', 'fa-file-word text-blue-500'],
                                ['Procès-verbal du conseil', 'ACME-P4M1XZ', null, null, 'v2', 'fa-file-pdf text-red-500'],
                                ['Politique RH 2026', 'ACME-R8T5LB', 'Brouillon', 'bg-slate-100 text-slate-600', 'v1', 'fa-file-word text-blue-500'],
                                ['Rapport financier T3', 'ACME-F2N7WC', 'Archivé', 'bg-violet-50 text-violet-700', 'v5', 'fa-file-pdf text-red-500'],
                            ] as $r => [$t, $ref, $s, $c, $v, $icon])
                                <div class="flex items-center gap-3 py-2.5 border-t border-slate-100 {{ $r === 0 ? 'border-t-0' : '' }}">
                                    <span class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center shrink-0"><i class="fa-solid {{ $icon }} text-sm"></i></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-bold truncate">{{ $t }}</p>
                                        <p class="text-[9px] text-slate-400 font-mono">{{ $ref }} · {{ $v }}</p>
                                    </div>
                                    @if($s)
                                        <span class="text-[9px] font-bold rounded-full px-2 py-0.5 {{ $c }}">{{ $s }}</span>
                                    @else
                                        <span class="text-[9px] font-bold rounded-full px-2 py-0.5 transition-all duration-500"
                                              :class="phase >= 2 ? 'bg-emerald-50 text-emerald-700 ring-4 ring-emerald-500/10' : 'bg-amber-50 text-amber-700'"
                                              x-text="phase >= 2 ? 'Approuvé' : 'En révision'">En révision</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Workflow (flottant) --}}
                <div class="float absolute -left-4 sm:-left-12 bottom-6 w-60 rounded-2xl bg-white/95 backdrop-blur border border-slate-200 shadow-2xl shadow-slate-900/10 p-4">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Workflow d'approbation</p>
                        <span class="text-[10px] font-bold tabular-nums" :class="phase >= 2 ? 'text-emerald-600' : 'text-slate-400'" x-text="(phase >= 2 ? 3 : 2) + '/3'">2/3</span>
                    </div>
                    <div class="h-1 rounded-full bg-slate-100 mb-3 overflow-hidden"><div class="h-full rounded-full bg-emerald-500 transition-all duration-700" :style="'width:' + (phase >= 2 ? 100 : 66) + '%'" style="width: 66%"></div></div>
                    @foreach(['Direction juridique', 'Direction financière'] as $step)
                        <div class="flex items-center gap-2 py-1">
                            <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center"><i class="fa-solid fa-check text-[8px]"></i></span>
                            <span class="text-[11px] font-semibold text-slate-700">{{ $step }}</span>
                        </div>
                    @endforeach
                    <div class="flex items-center gap-2 py-1">
                        <span class="w-5 h-5 rounded-full text-white flex items-center justify-center transition-colors duration-500"
                              :class="phase >= 2 ? 'bg-emerald-500' : (phase === 1 ? 'bg-orange-500' : 'bg-amber-400')">
                            <i class="fa-solid text-[8px]" :class="phase >= 2 ? 'fa-check' : (phase === 1 ? 'fa-spinner fa-spin' : 'fa-hourglass-half')"></i>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-700">Direction générale</span>
                    </div>
                </div>

                {{-- QR authentique (flottant) --}}
                <div class="float-2 absolute -right-2 sm:-right-8 -top-7 w-52 rounded-2xl bg-ink text-white shadow-2xl p-3.5 flex items-center gap-3">
                    <span class="relative w-10 h-10 rounded-lg bg-white flex items-center justify-center shrink-0 overflow-hidden">
                        <i class="fa-solid fa-qrcode text-ink text-lg"></i>
                        <span class="scan absolute inset-x-0 h-0.5 bg-orange-500 shadow-[0_0_8px_2px_rgba(249,115,22,.6)]"></span>
                    </span>
                    <span>
                        <span class="block text-[11px] font-bold">Document authentique</span>
                        <span class="block text-[10px] text-emerald-400 font-semibold"><i class="fa-solid fa-shield-halved mr-1"></i>Empreinte vérifiée</span>
                    </span>
                </div>

                {{-- Notification --}}
                <div x-show="phase === 2" x-cloak
                     x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-300" x-transition:leave-end="opacity-0 translate-x-6"
                     class="absolute right-2 sm:-right-6 bottom-16 w-60 rounded-2xl bg-white border border-slate-200 shadow-2xl shadow-slate-900/10 p-3 flex items-start gap-3">
                    <span class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-xs"></i></span>
                    <span>
                        <span class="block text-[11px] font-bold">Procès-verbal approuvé</span>
                        <span class="block text-[10px] text-slate-500">Toutes les étapes sont validées</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= SECTEURS (défilant) ================= --}}
<section class="border-y border-slate-100 bg-slate-50/60 py-9">
    <p class="text-center text-xs font-bold uppercase tracking-[.2em] text-slate-400 mb-7 px-4">Pensé pour les organisations qui ne peuvent pas perdre un document</p>
    <div class="marquee overflow-hidden">
        <div class="marquee-track">
            @for($loopTwice = 0; $loopTwice < 2; $loopTwice++)
                @foreach($sectors as [$icon, $label])
                    <div class="flex items-center gap-3 px-10 text-slate-500 shrink-0" @if($loopTwice) aria-hidden="true" @endif>
                        <i class="fa-solid {{ $icon }} text-xl text-slate-400"></i>
                        <span class="text-[15px] font-bold whitespace-nowrap">{{ $label }}</span>
                    </div>
                @endforeach
            @endfor
        </div>
    </div>
</section>

{{-- ================= REPÈRES ================= --}}
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 grid grid-cols-2 lg:grid-cols-4 gap-px rounded-3xl overflow-hidden border border-slate-200 bg-slate-200">
        @foreach([
            [config('saas.trial_days'), ' jours', 'd\'essai gratuit, sans engagement'],
            [4, ' étapes', 'de la création à l\'archivage légal'],
            [256, ' bits', 'empreinte SHA-256 par version'],
            [100, ' %', 'des actions journalisées et datées'],
        ] as $k => [$n, $unit, $label])
            <div class="reveal bg-white p-7 sm:p-9" style="--d: {{ $k * 90 }}ms">
                <p class="text-4xl sm:text-5xl font-extrabold tracking-[-0.04em] tabular-nums"><span data-count="{{ $n }}">{{ $n }}</span><span class="accent">{{ $unit }}</span></p>
                <p class="mt-2 text-sm text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= PROBLÈME / SOLUTION ================= --}}
<section class="pb-24 pt-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="max-w-3xl reveal">
            <p class="eyebrow">Pourquoi {{ $platform }}</p>
            <h2 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] leading-[1.08]">Les dossiers papier, les pièces jointes et les clés USB <span class="accent">coûtent cher.</span></h2>
            <p class="mt-5 text-lg text-slate-600">Versions contradictoires, signatures qui se perdent, documents introuvables au moment d'un audit… {{ $platform }} remplace ces pratiques par un processus clair et traçable.</p>
        </div>

        <div class="mt-14 grid md:grid-cols-2 gap-6">
            <div class="reveal rounded-3xl border border-slate-200 p-8 bg-white">
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-10 h-10 rounded-xl bg-red-50 text-red-500 flex items-center justify-center"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <p class="text-sm font-bold uppercase tracking-wider text-slate-400">Sans {{ $platform }}</p>
                </div>
                <ul class="space-y-4">
                    @foreach([
                        'On ne sait plus quelle version est la bonne',
                        'Les validations circulent par email et se perdent',
                        'Impossible de prouver qu\'un document n\'a pas été modifié',
                        'Les documents sensibles sont copiés sans contrôle',
                        'Retrouver un contrat de 2019 prend des heures',
                    ] as $k => $pain)
                        <li class="flex gap-3 text-slate-500"><i class="fa-solid fa-xmark text-red-400 mt-1"></i><span class="strike" style="--d: {{ $k * 180 }}ms">{{ $pain }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="reveal spot spot-dark rounded-3xl p-8 bg-ink text-white relative overflow-hidden" style="--d: 150ms">
                <div class="absolute inset-0 dots-dark -z-10"></div>
                <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-orange-600/25 blur-3xl -z-10"></div>
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-11 h-11 rounded-xl bg-white flex items-center justify-center shadow-lg shadow-orange-600/30"><x-logo class="w-9 h-9" /></span>
                    <p class="text-sm font-bold uppercase tracking-wider text-orange-400">Avec {{ $platform }}</p>
                </div>
                <ul class="space-y-4">
                    @foreach([
                        'Une version de référence, l\'historique complet derrière',
                        'Un circuit de validation dans l\'ordre, avec relances et motifs de rejet',
                        'Une empreinte SHA-256 et un QR code vérifiable sur chaque document',
                        'Des droits par personne, des partages qui expirent, un filigrane nominatif',
                        'Une recherche qui retrouve un document en quelques secondes',
                    ] as $gain)
                        <li class="flex gap-3 text-slate-200"><span class="w-5 h-5 mt-0.5 rounded-full bg-emerald-500/15 text-emerald-400 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-[9px]"></i></span><span>{{ $gain }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ================= FONCTIONNALITÉS (bento) ================= --}}
<section id="fonctionnalites" class="relative py-28 bg-slate-50 scroll-mt-20 overflow-hidden">
    <div class="absolute inset-0 dots opacity-60 [mask-image:linear-gradient(to_bottom,black,transparent_40%)]"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center max-w-2xl mx-auto reveal">
            <p class="eyebrow">Fonctionnalités</p>
            <h2 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] leading-[1.08]">Tout le cycle de vie du document, <span class="accent">au même endroit</span></h2>
            <p class="mt-5 text-lg text-slate-600">De la création à l'archivage légal, sans changer d'outil.</p>
        </div>

        <div class="mt-16 grid lg:grid-cols-6 gap-5">
            {{-- Versions --}}
            <div class="reveal spot lg:col-span-3 rounded-3xl bg-white border border-slate-200/80 p-8 overflow-hidden group">
                <div class="relative h-44 flex items-center justify-center mb-6">
                    @foreach([['ver-1', 'v1', 'bg-slate-50', 'Brouillon'], ['ver-2', 'v2', 'bg-white', 'Révisé'], ['ver-3', 'v3', 'bg-white', 'Approuvé']] as [$cls, $v, $bg, $lbl])
                        <div class="ver {{ $cls }} w-40 rounded-2xl border border-slate-200 {{ $bg }} shadow-xl shadow-slate-900/5 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-extrabold font-mono {{ $v === 'v3' ? 'text-orange-600' : 'text-slate-400' }}">{{ $v }}</span>
                                <span class="text-[9px] font-bold rounded-full px-1.5 py-0.5 {{ $v === 'v3' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $lbl }}</span>
                            </div>
                            <div class="mt-3 space-y-1.5"><div class="h-1.5 rounded bg-slate-200 w-full"></div><div class="h-1.5 rounded bg-slate-200 w-4/5"></div><div class="h-1.5 rounded bg-slate-200 w-3/5"></div></div>
                            <p class="mt-3 text-[8px] font-mono text-slate-400 truncate">sha256 · {{ substr(hash('sha256', $v), 0, 14) }}…</p>
                        </div>
                    @endforeach
                </div>
                <span class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors"><i class="fa-solid {{ $features[0][0] }}"></i></span>
                <h3 class="mt-5 text-xl font-extrabold tracking-tight">{{ $features[0][1] }}</h3>
                <p class="mt-2 text-slate-600 leading-relaxed">{{ $features[0][2] }}</p>
            </div>

            {{-- Workflow --}}
            <div class="reveal spot lg:col-span-3 rounded-3xl bg-white border border-slate-200/80 p-8 overflow-hidden group" style="--d: 100ms">
                <div class="relative h-44 flex items-center justify-center mb-6">
                    <div class="relative w-full max-w-sm flex items-center justify-between">
                        <div class="absolute left-6 right-6 top-6 h-[3px] rounded-full bg-slate-100"></div>
                        <div class="flow-line absolute left-6 right-6 top-6 h-[3px] rounded-full bg-emerald-500"></div>
                        @foreach([['Juridique', 'JU'], ['Finances', 'FI'], ['Direction', 'DG']] as $n => [$who, $ini])
                            <div class="relative flex flex-col items-center gap-2.5">
                                <span class="node w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xs font-extrabold ring-4 ring-white" style="--n: {{ $n }}">{{ $ini }}</span>
                                <span class="text-[11px] font-bold text-slate-500">{{ $who }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <span class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors"><i class="fa-solid {{ $features[1][0] }}"></i></span>
                <h3 class="mt-5 text-xl font-extrabold tracking-tight">{{ $features[1][1] }}</h3>
                <p class="mt-2 text-slate-600 leading-relaxed">{{ $features[1][2] }}</p>
            </div>

            {{-- Signature --}}
            <div class="reveal spot lg:col-span-2 rounded-3xl bg-white border border-slate-200/80 p-7 overflow-hidden group">
                <div class="h-32 rounded-2xl bg-slate-50 border border-dashed border-slate-200 flex flex-col items-center justify-center mb-6 relative">
                    <svg viewBox="0 0 230 90" class="w-48 h-20" fill="none">
                        <path class="sig-path" d="M8 62 C 22 20, 40 12, 38 48 S 52 82, 66 44 S 84 14, 92 52 C 96 70, 104 70, 112 46 S 126 24, 134 50 S 150 72, 162 40 C 168 26, 178 30, 176 50 S 196 64, 222 30" stroke="#0b1220" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="absolute bottom-3 left-6 right-6 h-px bg-slate-300"></span>
                </div>
                <span class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors"><i class="fa-solid {{ $features[2][0] }}"></i></span>
                <h3 class="mt-5 text-lg font-extrabold tracking-tight">{{ $features[2][1] }}</h3>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $features[2][2] }}</p>
            </div>

            {{-- QR code --}}
            <div class="reveal spot lg:col-span-2 rounded-3xl bg-white border border-slate-200/80 p-7 overflow-hidden group" style="--d: 100ms">
                <div class="h-32 rounded-2xl bg-slate-50 flex items-center justify-center mb-6 relative">
                    <div class="relative w-24 h-24 p-2 rounded-xl bg-white shadow-lg shadow-slate-900/5 overflow-hidden">
                        @php($qr = '1110101111011011101111010110100000110101111011101100101010010110101110010111101010100101011101101101011110110001011111010')
                        <div class="grid grid-cols-11 gap-[1.5px] w-full h-full">
                            @foreach(str_split($qr) as $bit)<span class="rounded-[1px] {{ $bit === '1' ? 'bg-ink' : 'bg-transparent' }}"></span>@endforeach
                        </div>
                        <span class="scan absolute inset-x-1 h-0.5 bg-orange-500 shadow-[0_0_10px_3px_rgba(249,115,22,.55)]"></span>
                    </div>
                    <span class="pop absolute top-3 right-3 text-[10px] font-bold rounded-full px-2 py-1 bg-emerald-500 text-white shadow-lg shadow-emerald-500/30"><i class="fa-solid fa-check mr-1"></i>Authentique</span>
                </div>
                <span class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors"><i class="fa-solid {{ $features[3][0] }}"></i></span>
                <h3 class="mt-5 text-lg font-extrabold tracking-tight">{{ $features[3][1] }}</h3>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $features[3][2] }}</p>
            </div>

            {{-- Recherche --}}
            <div class="reveal spot lg:col-span-2 rounded-3xl bg-white border border-slate-200/80 p-7 overflow-hidden group" style="--d: 200ms"
                 x-data="typer(['contrat fournisseur 2019', 'procès-verbal conseil', 'ACME-7K2Q9D', 'politique RH'])">
                <div class="h-32 rounded-2xl bg-slate-50 flex flex-col justify-center gap-2.5 mb-6 px-4">
                    <div class="h-10 rounded-xl bg-white border border-orange-300 ring-4 ring-orange-500/10 flex items-center gap-2.5 px-3 text-sm">
                        <i class="fa-solid fa-magnifying-glass text-xs text-slate-400"></i>
                        <span class="font-semibold text-slate-800 truncate" x-text="text"></span><span class="caret"></span>
                    </div>
                    <div class="flex items-center gap-2 px-1 text-[11px] text-slate-500 transition-opacity duration-300" :class="done ? 'opacity-100' : 'opacity-0'">
                        <i class="fa-solid fa-file-pdf text-red-500"></i><span class="font-bold text-slate-700">1 résultat</span> · en 0,3 s
                    </div>
                </div>
                <span class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition-colors"><i class="fa-solid {{ $features[4][0] }}"></i></span>
                <h3 class="mt-5 text-lg font-extrabold tracking-tight">{{ $features[4][1] }}</h3>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $features[4][2] }}</p>
            </div>
        </div>

        {{-- Autres fonctionnalités --}}
        <div class="mt-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach(array_slice($features, 5) as $k => [$icon, $title, $text])
                <div class="reveal spot group rounded-3xl bg-white border border-slate-200/80 p-6 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/5" style="--d: {{ $k * 80 }}ms">
                    <span class="w-11 h-11 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white group-hover:rotate-[-6deg] transition"><i class="fa-solid {{ $icon }}"></i></span>
                    <h3 class="mt-5 text-base font-extrabold">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $text }}</p>
                </div>
            @endforeach
        </div>

        <div class="reveal mt-8 marquee overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-white/70 py-4">
            <div class="marquee-track" style="animation-duration: 30s; animation-direction: reverse">
                @for($loopTwice = 0; $loopTwice < 2; $loopTwice++)
                    @foreach([
                        ['fa-file-word', 'Création de documents Word'], ['fa-file-import', 'Import Word, PDF, Excel, PowerPoint, images'], ['fa-lock', 'Verrouillage pendant l\'édition'],
                        ['fa-comments', 'Commentaires'], ['fa-bell', 'Notifications et rappels'], ['fa-layer-group', 'Actions en masse'],
                        ['fa-chart-pie', 'Rapports et export CSV'], ['fa-star', 'Favoris'],
                    ] as [$icon, $label])
                        <span class="inline-flex items-center gap-2 px-6 text-sm font-semibold text-slate-600 whitespace-nowrap" @if($loopTwice) aria-hidden="true" @endif><i class="fa-solid {{ $icon }} text-orange-500"></i>{{ $label }}</span>
                    @endforeach
                @endfor
            </div>
        </div>
    </div>
</section>

{{-- ================= FONCTIONNEMENT ================= --}}
<section id="fonctionnement" class="py-28 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="max-w-2xl reveal">
            <p class="eyebrow">Fonctionnement</p>
            <h2 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] leading-[1.08]">Un circuit simple, que vos équipes adoptent <span class="accent">dès le premier jour</span></h2>
        </div>

        <ol id="steps" class="relative mt-16 grid md:grid-cols-4 gap-10 md:gap-6">
            <span class="rail hidden md:block absolute top-7 left-[7%] right-[7%] h-[3px] rounded-full bg-slate-100"></span>
            @foreach([
                ['fa-file-circle-plus', 'Créer ou importer', 'Générez un document Word à votre en-tête ou importez vos fichiers existants. Une référence unique et un QR code sont attribués.'],
                ['fa-users-gear', 'Faire valider', 'Choisissez les approbateurs et leur ordre. Chacun reçoit une notification lorsque c\'est son tour.'],
                ['fa-pen-nib', 'Signer et certifier', 'Les signataires signent à l\'écran. La signature est liée à l\'empreinte exacte du fichier.'],
                ['fa-vault', 'Archiver et retrouver', 'Le document est archivé selon la durée de conservation de sa catégorie, et reste retrouvable en un instant.'],
            ] as $i => [$icon, $title, $text])
                <li data-step class="reveal relative" style="--d: {{ $i * 120 }}ms">
                    <span class="badge-step relative w-14 h-14 rounded-2xl bg-ink text-white flex items-center justify-center shadow-lg">
                        <i class="fa-solid {{ $icon }} text-lg"></i>
                        <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-white text-ink text-[11px] font-extrabold flex items-center justify-center ring-1 ring-slate-200 shadow">{{ $i + 1 }}</span>
                    </span>
                    <h3 class="mt-6 text-lg font-extrabold tracking-tight">{{ $title }}</h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed">{{ $text }}</p>
                </li>
            @endforeach
        </ol>

        {{-- Vérification publique --}}
        <div class="reveal mt-20 relative overflow-hidden grid lg:grid-cols-2 gap-12 items-center rounded-[2rem] bg-gradient-to-br from-orange-50 via-white to-white border border-orange-100 p-8 sm:p-14">
            <div class="absolute -top-24 -left-24 w-80 h-80 rounded-full bg-orange-200/40 blur-3xl"></div>
            <div class="relative">
                <span class="inline-flex items-center gap-2 text-xs font-bold text-orange-700 bg-white border border-orange-200 rounded-full px-3 py-1.5 shadow-sm"><i class="fa-solid fa-qrcode"></i> Vérification publique</span>
                <h3 class="mt-5 text-3xl sm:text-4xl font-extrabold tracking-[-0.03em] leading-tight">Un document imprimé reste <span class="accent">vérifiable</span></h3>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">Un client, une banque ou une administration scanne le QR code présent sur le document : une page sécurisée confirme sa référence, son émetteur et son authenticité. La falsification devient visible.</p>
            </div>
            <div class="relative flex justify-center">
                {{-- Téléphone --}}
                <div class="float relative w-64 rounded-[2.2rem] bg-ink p-2.5 shadow-2xl shadow-slate-900/30">
                    <div class="absolute top-4 left-1/2 -translate-x-1/2 w-16 h-4 rounded-full bg-black z-10"></div>
                    <div class="rounded-[1.8rem] bg-white overflow-hidden pt-10 pb-6 px-5">
                        <div class="flex flex-col items-center text-center">
                            <span class="relative w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <span class="absolute inset-0 rounded-full bg-emerald-400/30 pulse-ring"></span>
                                <i class="relative fa-solid fa-shield-halved text-2xl"></i>
                            </span>
                            <p class="mt-4 font-extrabold">Document authentique</p>
                            <p class="text-xs text-slate-500">Vérifié le {{ now()->format('d/m/Y') }}</p>
                        </div>
                        <dl class="mt-5 space-y-2.5 text-xs">
                            <div class="flex justify-between"><dt class="text-slate-500">Référence</dt><dd class="font-mono font-bold">ACME-7K2Q9D</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Émetteur</dt><dd class="font-semibold">ACME SA</dd></div>
                            <div class="flex justify-between"><dt class="text-slate-500">Version</dt><dd class="font-semibold">v3</dd></div>
                            <div class="flex justify-between items-center"><dt class="text-slate-500">Statut</dt><dd><span class="font-bold text-emerald-700 bg-emerald-50 rounded-full px-2 py-0.5">Approuvé</span></dd></div>
                        </dl>
                        <div class="mt-5 rounded-xl bg-slate-50 px-3 py-2 text-[9px] font-mono text-slate-400 truncate">sha256 · {{ hash('sha256', 'ACME-7K2Q9D') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= SÉCURITÉ ================= --}}
<section id="securite" class="relative py-28 bg-ink text-white overflow-hidden scroll-mt-20">
    <div class="absolute inset-0 dots-dark"></div>
    <div class="blob w-[520px] h-[520px] bg-orange-600/25 -bottom-48 -left-40"></div>
    <div class="blob w-[360px] h-[360px] bg-orange-500/10 top-10 right-0" style="animation-delay: -8s"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-2 gap-14 items-center">
            <div class="reveal">
                <p class="eyebrow !text-orange-400">Sécurité et conformité</p>
                <h2 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] leading-[1.08]">La confiance se prouve. <span class="accent">{{ $platform }} garde la preuve.</span></h2>
                <p class="mt-5 text-lg text-slate-400 leading-relaxed">Chaque mécanisme répond à un risque concret : accès non autorisé, falsification, fuite, suppression accidentelle ou contestation.</p>
                <a href="#contact" class="group mt-9 inline-flex items-center gap-2 px-6 py-3.5 rounded-2xl bg-white text-ink font-bold hover:bg-orange-50 transition">
                    Parler à un expert <i class="fa-solid fa-arrow-right text-xs transition group-hover:translate-x-1"></i>
                </a>
            </div>

            {{-- Orbite --}}
            <div class="reveal hidden lg:flex justify-center" aria-hidden="true">
                <div class="relative w-[420px] h-[420px]">
                    <div class="absolute inset-0 rounded-full border border-white/10"></div>
                    <div class="absolute inset-[70px] rounded-full border border-white/10 border-dashed"></div>
                    <div class="absolute inset-[140px] rounded-full bg-orange-600/20 blur-2xl"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="relative w-28 h-28 rounded-[2rem] bg-gradient-to-br from-orange-500 to-orange-700 flex items-center justify-center shadow-2xl shadow-orange-600/40">
                            <span class="absolute inset-0 rounded-[2rem] bg-orange-500/50 pulse-ring"></span>
                            <i class="relative fa-solid fa-shield-halved text-5xl text-white"></i>
                        </span>
                    </div>
                    <div class="orbit absolute inset-0">
                        @foreach(['fa-building-lock', 'fa-id-badge', 'fa-fingerprint', 'fa-scroll', 'fa-gavel', 'fa-key'] as $k => $icon)
                            @php($a = $k * 60)
                            <div class="orbit-item absolute left-1/2 top-1/2 -ml-6 -mt-6" style="--a: {{ $a }}deg; transform: rotate({{ $a }}deg) translateX(210px)">
                                <span class="w-12 h-12 rounded-2xl bg-white/[.06] border border-white/15 backdrop-blur flex items-center justify-center text-orange-400 shadow-xl" style="--a: {{ $a }}deg">
                                    <i class="fa-solid {{ $icon }}"></i>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                ['fa-building-lock', 'Données cloisonnées', 'En SaaS, chaque entreprise dispose d\'un espace strictement isolé : documents, utilisateurs, paramètres.'],
                ['fa-id-badge', 'Rôles et droits', 'Administrateur, éditeur, lecteur, plus des droits par document : lecture, commentaire, édition.'],
                ['fa-fingerprint', 'Intégrité vérifiable', 'Empreinte SHA-256 de chaque version ; une signature indique si le fichier a changé depuis.'],
                ['fa-scroll', 'Journal d\'audit', 'Qui a consulté, téléchargé, modifié, partagé ou signé — avec date, IP et navigateur.'],
                ['fa-gavel', 'Gel juridique', 'Le Legal Hold bloque toute modification ou suppression d\'un document sous contentieux.'],
                ['fa-key', 'Comptes protégés', 'Limitation des tentatives de connexion, réinitialisation par lien à usage unique, sessions révocables.'],
            ] as $k => [$icon, $title, $text])
                <div class="reveal spot spot-dark group rounded-3xl border border-white/10 bg-white/[.03] p-6 hover:border-orange-500/40 transition" style="--d: {{ ($k % 3) * 90 }}ms">
                    <span class="w-11 h-11 rounded-xl bg-white/[.06] border border-white/10 text-orange-400 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white group-hover:border-orange-600 transition"><i class="fa-solid {{ $icon }}"></i></span>
                    <h3 class="mt-4 font-bold text-lg">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm text-slate-400 leading-relaxed">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= FORMULES ================= --}}
<section id="formules" class="py-28 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center max-w-2xl mx-auto reveal">
            <p class="eyebrow">Formules</p>
            <h2 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] leading-[1.08]">Deux façons d'adopter <span class="accent">{{ $platform }}</span></h2>
            <p class="mt-5 text-lg text-slate-600">Le même produit, deux modèles de déploiement. Choisissez selon vos contraintes d'hébergement et de budget.</p>
        </div>

        <div class="mt-16 grid lg:grid-cols-2 gap-6">
            {{-- SaaS --}}
            <div class="reveal glow-border shadow-2xl shadow-orange-600/15">
                <div class="relative h-full rounded-[1.65rem] bg-white p-8 sm:p-10 flex flex-col">
                    <span class="absolute -top-px left-10 -translate-y-1/2 rounded-full bg-orange-600 text-white text-[11px] font-bold px-3 py-1 shadow-lg shadow-orange-600/30">Recommandé pour les PME</span>
                    <div class="flex items-center gap-4">
                        <span class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-cloud text-xl"></i></span>
                        <div>
                            <h3 class="text-3xl font-extrabold tracking-tight">SaaS</h3>
                            <p class="text-sm text-slate-500">Hébergé et maintenu par {{ $vendor }}</p>
                        </div>
                    </div>
                    <p class="mt-6 text-slate-600 leading-relaxed">Vous vous connectez, vous travaillez. Pas de serveur à acheter ni à maintenir : nous gérons l'hébergement, la maintenance et les mises à jour.</p>
                    <ul class="mt-7 space-y-3.5 text-sm">
                        @foreach([
                            'Démarrage rapide, sans installation',
                            'Abonnement mensuel ou annuel, sans investissement initial',
                            'Mises à jour et nouvelles fonctionnalités incluses',
                            'Espace isolé et sécurisé pour votre entreprise',
                            'Essai gratuit de ' . config('saas.trial_days') . ' jours',
                        ] as $item)
                            <li class="flex gap-3"><span class="w-5 h-5 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-[9px]"></i></span><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                    <div class="mt-auto pt-9">
                        <button type="button" @click="choose('saas')" class="group w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-orange-600 hover:bg-orange-500 text-white font-bold shadow-lg shadow-orange-600/25 transition">
                            Démarrer l'essai gratuit <i class="fa-solid fa-arrow-right text-xs transition group-hover:translate-x-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- SingleEntity --}}
            <div class="reveal spot spot-dark relative rounded-[1.75rem] bg-ink text-white p-8 sm:p-10 overflow-hidden flex flex-col" style="--d: 120ms">
                <div class="absolute inset-0 dots-dark -z-10"></div>
                <div class="absolute -bottom-24 -right-24 w-80 h-80 rounded-full bg-orange-600/20 blur-3xl -z-10"></div>
                <div class="flex items-center gap-4">
                    <span class="w-14 h-14 rounded-2xl bg-white/10 text-orange-400 flex items-center justify-center"><i class="fa-solid fa-server text-xl"></i></span>
                    <div>
                        <h3 class="text-3xl font-extrabold tracking-tight">SingleEntity</h3>
                        <p class="text-sm text-slate-400">Installation dédiée à votre organisation</p>
                    </div>
                </div>
                <p class="mt-6 text-slate-300 leading-relaxed">{{ $platform }} installée sur vos serveurs ou votre cloud privé. Vos documents ne quittent jamais votre infrastructure : idéal pour les banques, les administrations et les groupes.</p>
                <ul class="mt-7 space-y-3.5 text-sm">
                    @foreach([
                        'Données hébergées chez vous, sous votre contrôle',
                        'Utilisateurs et stockage sans limite d\'offre',
                        'À vos couleurs : nom, en-tête des documents, catégories',
                        'Installation, paramétrage et formation de vos équipes',
                        'Licence annuelle avec support et mises à jour',
                    ] as $item)
                        <li class="flex gap-3 text-slate-200"><span class="w-5 h-5 rounded-full bg-white/10 text-orange-400 flex items-center justify-center shrink-0"><i class="fa-solid fa-check text-[9px]"></i></span><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
                <div class="mt-auto pt-9">
                    <p class="text-sm text-slate-400 mb-3">Tarif sur devis, selon votre infrastructure et vos besoins.</p>
                    <button type="button" @click="choose('single_entity')" class="w-full px-6 py-4 rounded-2xl bg-white text-ink hover:bg-orange-50 font-bold transition">
                        Demander un devis
                    </button>
                </div>
            </div>
        </div>

        {{-- Offres SaaS (gérées depuis l'espace super admin) --}}
        @if($plans->isNotEmpty())
            <div class="mt-24">
                <div class="text-center reveal">
                    <h3 class="text-3xl font-extrabold tracking-[-0.02em]">Les offres SaaS</h3>
                    <p class="mt-2 text-slate-600">Changez d'offre à tout moment en fonction de votre croissance.</p>
                </div>
                <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-{{ min(4, max(1, $plans->count())) }} gap-5 items-stretch">
                    @foreach($plans as $index => $plan)
                        @php($highlight = $plans->count() > 2 && $index === 1)
                        <div class="reveal spot {{ $highlight ? 'spot-dark' : '' }} relative rounded-3xl p-7 flex flex-col transition duration-300 hover:-translate-y-1.5 {{ $highlight ? 'bg-ink text-white shadow-2xl shadow-slate-900/30 lg:scale-[1.03]' : 'bg-white border border-slate-200 hover:shadow-xl hover:shadow-slate-900/5' }}" style="--d: {{ $index * 100 }}ms">
                            @if($highlight)<span class="absolute -top-3 right-6 rounded-full bg-orange-600 text-white text-[10px] font-bold px-2.5 py-1 shadow-lg shadow-orange-600/30">Recommandé</span>@endif
                            <h4 class="text-lg font-extrabold">{{ $plan->name }}</h4>
                            @if($plan->description)<p class="text-sm {{ $highlight ? 'text-slate-400' : 'text-slate-500' }}">{{ $plan->description }}</p>@endif
                            <p class="mt-6 flex items-baseline gap-1.5 flex-wrap">
                                <span class="text-4xl font-extrabold tracking-[-0.04em] tabular-nums">{{ number_format($plan->price, 0, ',', ' ') }}</span>
                                <span class="text-sm font-semibold {{ $highlight ? 'text-slate-400' : 'text-slate-500' }}">{{ $plan->currency }} / {{ $plan->periodLabel() }}</span>
                            </p>
                            <ul class="mt-7 pt-6 border-t {{ $highlight ? 'border-white/10' : 'border-slate-100' }} space-y-3 text-sm flex-1">
                                <li class="flex gap-2.5"><i class="fa-solid fa-users mt-0.5 {{ $highlight ? 'text-orange-400' : 'text-orange-600' }}"></i>{{ $plan->max_users ? $plan->max_users . ' utilisateurs' : 'Utilisateurs illimités' }}</li>
                                <li class="flex gap-2.5"><i class="fa-solid fa-hard-drive mt-0.5 {{ $highlight ? 'text-orange-400' : 'text-orange-600' }}"></i>{{ $plan->max_storage_mb ? formatBytes($plan->max_storage_mb * 1024 * 1024) . ' de stockage' : 'Stockage illimité' }}</li>
                                @foreach($plan->features ?? [] as $feature)
                                    <li class="flex gap-2.5"><i class="fa-solid fa-check mt-0.5 {{ $highlight ? 'text-emerald-400' : 'text-emerald-500' }}"></i>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <button type="button" @click="choose('saas')"
                                    class="mt-7 w-full px-5 py-3.5 rounded-2xl font-bold text-sm transition {{ $highlight ? 'bg-orange-600 hover:bg-orange-500 text-white shadow-lg shadow-orange-600/30' : 'bg-slate-100 hover:bg-ink hover:text-white text-slate-900' }}">
                                Choisir {{ $plan->name }}
                            </button>
                        </div>
                    @endforeach
                </div>
                <p class="mt-7 text-center text-xs text-slate-400">Paiement par Orange Money, Wave, Moov Money, virement, chèque ou espèces.</p>
            </div>
        @endif

        {{-- Comparatif --}}
        <div class="mt-24 reveal">
            <h3 class="text-center text-3xl font-extrabold tracking-[-0.02em]">SaaS ou SingleEntity ?</h3>
            <div class="mt-10 overflow-x-auto rounded-3xl border border-slate-200 shadow-xl shadow-slate-900/[.03]">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                    <tr class="bg-slate-50 text-left">
                        <th class="px-6 py-5 font-bold text-slate-500">Critère</th>
                        <th class="px-6 py-5 font-extrabold text-orange-600"><i class="fa-solid fa-cloud mr-1.5"></i>SaaS</th>
                        <th class="px-6 py-5 font-extrabold text-ink"><i class="fa-solid fa-server mr-1.5"></i>SingleEntity</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach([
                        ['Hébergement', 'Plateforme ' . $vendor, 'Vos serveurs ou votre cloud privé'],
                        ['Mise en route', 'Immédiate', 'Installation accompagnée'],
                        ['Modèle de prix', 'Abonnement mensuel ou annuel', 'Licence annuelle sur devis'],
                        ['Utilisateurs et stockage', 'Selon l\'offre choisie', 'Sans limite d\'offre'],
                        ['Maintenance et mises à jour', 'Incluses, gérées par nos soins', 'Incluses dans la licence'],
                        ['Personnalisation', 'Nom, en-tête, catégories, préfixes', 'Complète, à votre identité'],
                        ['Idéal pour', 'PME, cabinets, ONG, filiales', 'Banques, administrations, groupes'],
                    ] as [$label, $saas, $single])
                        <tr class="hover:bg-orange-50/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-700">{{ $label }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $saas }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $single }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ================= FAQ ================= --}}
<section id="faq" class="py-28 bg-slate-50 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 grid lg:grid-cols-12 gap-12">
        <div class="lg:col-span-4 reveal">
            <p class="eyebrow">Questions fréquentes</p>
            <h2 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] leading-[1.08]">Vous vous posez <span class="accent">la question ?</span></h2>
            <p class="mt-5 text-slate-600">Vous ne trouvez pas votre réponse ? Écrivez-nous, nous répondons rapidement.</p>
            <a href="mailto:{{ config('saas.support_email') }}" class="mt-6 inline-flex items-center gap-2 font-bold text-orange-600 hover:text-orange-700">{{ config('saas.support_email') }} <i class="fa-solid fa-arrow-right text-xs"></i></a>
        </div>
        <div class="lg:col-span-8 space-y-3" x-data="{ open: 0 }">
            @foreach([
                ['Où sont stockés nos documents ?', 'En SaaS, sur la plateforme hébergée par ' . $vendor . ', dans un espace strictement isolé des autres entreprises. En SingleEntity, sur vos propres serveurs : vos documents ne quittent pas votre infrastructure.'],
                ['Peut-on essayer avant de s\'engager ?', 'Oui. Nous ouvrons votre espace SaaS avec un essai gratuit de ' . config('saas.trial_days') . ' jours, sans engagement. Pour SingleEntity, nous organisons une démonstration sur vos cas d\'usage.'],
                ['Comment se fait le paiement ?', 'Par Orange Money, Wave, Moov Money, virement bancaire, chèque ou espèces. Nous enregistrons votre paiement et votre abonnement est activé ou prolongé.'],
                ['Quels formats de documents sont pris en charge ?', 'Word (.docx, .doc, .odt), PDF, Excel (.xlsx, .xls, .csv), PowerPoint (.pptx, .ppt), images (JPG, PNG, TIFF), fichiers texte et archives ZIP, avec un aperçu directement dans le navigateur. Vous pouvez aussi créer des documents Word à l\'en-tête de votre entreprise, avec leur référence et leur QR code.'],
                ['Que se passe-t-il à la fin de l\'abonnement ?', 'L\'accès est suspendu, mais vos documents sont conservés en sécurité. Dès le renouvellement, vos équipes retrouvent leur espace exactement comme elles l\'ont laissé.'],
                ['Peut-on passer du SaaS au SingleEntity ?', 'Oui. Si vos besoins évoluent, nous vous accompagnons pour installer ' . $platform . ' chez vous et y transférer vos documents.'],
                ['Nos équipes seront-elles formées ?', 'Un guide interactif est intégré à l\'application pour chaque rôle. En SingleEntity, la formation de vos équipes fait partie du déploiement.'],
            ] as $i => [$q, $a])
                <div class="reveal rounded-2xl bg-white border transition-all duration-300" style="--d: {{ $i * 50 }}ms"
                     :class="open === {{ $i }} ? 'border-orange-200 shadow-lg shadow-orange-900/5' : 'border-slate-200 hover:border-slate-300'">
                    <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left">
                        <span class="font-bold">{{ $q }}</span>
                        <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 transition-all duration-300"
                              :class="open === {{ $i }} ? 'bg-orange-600 text-white rotate-45' : 'bg-slate-100 text-slate-500'">
                            <i class="fa-solid fa-plus text-xs"></i>
                        </span>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak class="px-6 pb-6 -mt-1 text-slate-600 leading-relaxed">{{ $a }}</div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================= CONTACT / DÉMO ================= --}}
<section id="contact" class="py-28 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 grid lg:grid-cols-12 gap-12">
        <div class="lg:col-span-5 reveal">
            <p class="eyebrow">Démo et devis</p>
            <h2 class="mt-4 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] leading-[1.08]">Voyons ensemble comment {{ $platform }} s'adapte à <span class="accent">votre organisation</span></h2>
            <p class="mt-5 text-lg text-slate-600">Décrivez-nous votre besoin : nous revenons vers vous pour une démonstration sur vos propres cas d'usage.</p>

            <div class="mt-10 space-y-3">
                <a href="mailto:{{ config('saas.support_email') }}" class="group flex items-center gap-4 p-4 rounded-2xl border border-slate-200 hover:border-orange-300 hover:shadow-lg hover:shadow-orange-900/5 transition">
                    <span class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition"><i class="fa-solid fa-envelope"></i></span>
                    <span class="flex-1"><span class="block text-xs text-slate-500">Email</span><span class="font-bold">{{ config('saas.support_email') }}</span></span>
                    <i class="fa-solid fa-arrow-right text-xs text-slate-300 group-hover:text-orange-600 group-hover:translate-x-1 transition"></i>
                </a>
                <a href="tel:{{ $phoneTel }}" class="group flex items-center gap-4 p-4 rounded-2xl border border-slate-200 hover:border-orange-300 hover:shadow-lg hover:shadow-orange-900/5 transition">
                    <span class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-600 group-hover:text-white transition"><i class="fa-solid fa-phone"></i></span>
                    <span class="flex-1"><span class="block text-xs text-slate-500">Téléphone</span><span class="font-bold">{{ config('saas.support_phone') }}</span></span>
                    <i class="fa-solid fa-arrow-right text-xs text-slate-300 group-hover:text-orange-600 group-hover:translate-x-1 transition"></i>
                </a>
                <div class="flex items-center gap-4 p-4 rounded-2xl border border-slate-200">
                    <span class="w-12 h-12 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-location-dot"></i></span>
                    <span><span class="block text-xs text-slate-500">{{ $vendor }}</span><span class="font-bold">{{ config('saas.vendor_city') }}</span></span>
                </div>
            </div>

            {{-- Conseillère support : elle désigne le formulaire --}}
            <div class="relative mt-10 flex items-end gap-2">
                <div class="absolute bottom-0 left-4 w-44 h-44 rounded-full bg-orange-200/50 blur-3xl"></div>
                <img src="{{ asset('images/hotesse-support.webp') }}" alt="" aria-hidden="true" width="600" height="900" loading="lazy" decoding="async"
                     class="relative h-60 sm:h-72 w-auto drop-shadow-[0_20px_25px_rgba(234,88,12,.18)] select-none" draggable="false">
                <div class="relative mb-24 sm:mb-32 max-w-[15rem] rounded-2xl rounded-bl-sm bg-white border border-orange-100 shadow-xl shadow-orange-900/5 px-4 py-3">
                    <p class="text-sm font-bold text-slate-900">Une question ?</p>
                    <p class="mt-1 text-xs text-slate-500 leading-relaxed">Notre équipe {{ $vendor }} vous répond et vous accompagne dans la mise en place.</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-7 reveal" style="--d: 120ms">
            @if(session('demo_success'))
                <div class="relative overflow-hidden rounded-[2rem] border border-emerald-200 bg-emerald-50 p-12 text-center">
                    <span class="relative w-20 h-20 mx-auto rounded-full bg-emerald-500 text-white flex items-center justify-center text-3xl shadow-xl shadow-emerald-500/30">
                        <span class="absolute inset-0 rounded-full bg-emerald-400 pulse-ring"></span>
                        <i class="relative fa-solid fa-check"></i>
                    </span>
                    <h3 class="mt-6 text-2xl font-extrabold">Merci, votre demande est bien reçue.</h3>
                    <p class="mt-2 text-slate-600">L'équipe {{ $vendor }} vous recontacte très rapidement pour organiser votre démonstration.</p>
                </div>
            @else
                <form method="POST" action="{{ route('demo-request.store') }}" class="relative rounded-[2rem] border border-slate-200 bg-white p-6 sm:p-9 shadow-2xl shadow-slate-900/[.06]">
                    @csrf
                    {{-- Champ piège anti-robots --}}
                    <div class="hidden" aria-hidden="true"><label>Site web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <p class="field-label">Formule qui vous intéresse</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mb-7 p-1.5 rounded-2xl bg-slate-100/80">
                        @foreach(['saas' => ['fa-cloud', 'SaaS'], 'single_entity' => ['fa-server', 'SingleEntity'], 'unsure' => ['fa-compass', 'Conseillez-moi']] as $value => [$icon, $label])
                            <label class="flex items-center justify-center gap-2 px-3 py-3 rounded-xl cursor-pointer text-sm font-bold transition-all duration-300"
                                   :class="formula === '{{ $value }}' ? 'bg-white text-orange-700 shadow-md shadow-slate-900/5' : 'text-slate-500 hover:text-slate-800'">
                                <input type="radio" name="formula" value="{{ $value }}" x-model="formula" class="sr-only">
                                <i class="fa-solid {{ $icon }}"></i> {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="field-label" for="company">Entreprise *</label>
                            <input id="company" type="text" name="company" value="{{ old('company') }}" required class="field" autocomplete="organization">
                        </div>
                        <div>
                            <label class="field-label" for="contact_name">Nom et prénom *</label>
                            <input id="contact_name" type="text" name="contact_name" value="{{ old('contact_name') }}" required class="field" autocomplete="name">
                        </div>
                        <div>
                            <label class="field-label" for="email">Email professionnel *</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="field" autocomplete="email">
                        </div>
                        <div>
                            <label class="field-label" for="phone">Téléphone</label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="field" autocomplete="tel" placeholder="+223 …">
                        </div>
                        <div>
                            <label class="field-label" for="company_size">Taille de l'entreprise</label>
                            <select id="company_size" name="company_size" class="field">
                                <option value="">—</option>
                                @foreach(\App\Models\DemoRequest::SIZES as $size)
                                    <option value="{{ $size }}" @selected(old('company_size') === $size)>{{ $size }} employés</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="field-label" for="sector">Secteur d'activité</label>
                            <input id="sector" type="text" name="sector" value="{{ old('sector') }}" class="field" placeholder="Banque, BTP, santé…">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="field-label" for="message">Votre besoin</label>
                            <textarea id="message" name="message" rows="4" class="field" placeholder="Volume de documents, nombre d'utilisateurs, contraintes d'hébergement…">{{ old('message') }}</textarea>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="mt-5 rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-700">
                            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                        </div>
                    @endif

                    <button class="group relative mt-7 w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-orange-600 hover:bg-orange-500 text-white font-bold shadow-xl shadow-orange-600/25 overflow-hidden transition">
                        <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/25 to-transparent"></span>
                        <span class="relative">Envoyer ma demande</span>
                        <i class="relative fa-solid fa-paper-plane text-xs transition group-hover:translate-x-1 group-hover:-translate-y-0.5"></i>
                    </button>
                    <p class="mt-3 text-center text-xs text-slate-400">Vos informations servent uniquement à vous recontacter au sujet de {{ $platform }}.</p>
                </form>
            @endif
        </div>
    </div>
</section>

{{-- ================= CTA FINAL ================= --}}
<section class="pb-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-ink px-6 py-20 sm:px-14 text-center">
            <div class="absolute inset-0 dots-dark"></div>
            <div class="blob w-[600px] h-[320px] bg-orange-600/35 -top-32 left-1/2 -ml-[300px]"></div>
            <div class="blob w-[300px] h-[300px] bg-amber-500/15 -bottom-32 right-10" style="animation-delay: -5s"></div>
            {{-- Conseillère qui présente l'offre (grands écrans) --}}
            <div class="hidden xl:block absolute bottom-0 left-10 h-[92%] pointer-events-none" aria-hidden="true">
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-64 h-40 rounded-full bg-orange-500/30 blur-3xl"></div>
                <img src="{{ asset('images/conseillere-offre.webp') }}" alt="" width="555" height="900" loading="lazy" decoding="async"
                     class="relative h-full w-auto select-none" draggable="false">
            </div>
            <div class="relative">
                <span class="inline-flex w-16 h-16 rounded-2xl bg-white items-center justify-center shadow-xl shadow-orange-600/20 float"><x-logo class="w-12 h-12" /></span>
                <h2 class="mt-7 text-3xl sm:text-5xl font-extrabold tracking-[-0.03em] text-white leading-[1.08]">Prêt à mettre de l'ordre<br class="hidden sm:block"> dans <span class="accent">vos documents ?</span></h2>
                <p class="mt-5 text-lg text-slate-300 max-w-2xl mx-auto">Rejoignez les organisations qui ont choisi la traçabilité et la sérénité.</p>
                <div class="mt-10 flex flex-col sm:flex-row gap-3 justify-center">
                    <button type="button" @click="choose('saas')" class="group inline-flex items-center justify-center gap-2 px-7 py-4 rounded-2xl bg-orange-600 hover:bg-orange-500 text-white font-bold shadow-xl shadow-orange-600/30 transition hover:-translate-y-0.5">
                        Essayer gratuitement <i class="fa-solid fa-arrow-right text-xs transition group-hover:translate-x-1"></i>
                    </button>
                    <a href="{{ route('login') }}" class="px-7 py-4 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold border border-white/15 transition">J'ai déjà un compte</a>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

{{-- ================= PIED DE PAGE ================= --}}
<footer class="relative border-t border-slate-200 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-14 grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
        <div class="lg:col-span-2">
            <div class="flex items-center gap-2.5">
                <x-logo class="w-10 h-10" />
                <span class="text-[17px] font-extrabold tracking-tight">{{ $platform }}</span>
            </div>
            <p class="mt-4 text-sm text-slate-500 max-w-sm leading-relaxed">La gestion électronique de documents des entreprises exigeantes. Une solution conçue et opérée par {{ $vendor }}.</p>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Produit</p>
            <ul class="mt-4 space-y-2.5 text-sm font-semibold text-slate-600">
                <li><a href="#fonctionnalites" class="hover:text-orange-600 transition">Fonctionnalités</a></li>
                <li><a href="#securite" class="hover:text-orange-600 transition">Sécurité</a></li>
                <li><a href="#formules" class="hover:text-orange-600 transition">Formules</a></li>
                <li><a href="#faq" class="hover:text-orange-600 transition">FAQ</a></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $vendor }}</p>
            <ul class="mt-4 space-y-2.5 text-sm font-semibold text-slate-600">
                <li><a href="mailto:{{ config('saas.support_email') }}" class="hover:text-orange-600 transition">{{ config('saas.support_email') }}</a></li>
                <li><a href="tel:{{ $phoneTel }}" class="hover:text-orange-600 transition">{{ config('saas.support_phone') }}</a></li>
                <li>{{ config('saas.vendor_city') }}</li>
                <li><a href="{{ route('login') }}" class="hover:text-orange-600 transition">Espace client</a></li>
            </ul>
        </div>
    </div>
    {{-- Grand logotype --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 select-none pointer-events-none" aria-hidden="true">
        <p class="text-[22vw] lg:text-[16rem] leading-[.8] font-extrabold tracking-[-0.06em] text-transparent bg-clip-text bg-gradient-to-b from-slate-100 to-white -mb-6">{{ $platform }}</p>
    </div>
    <div class="relative border-t border-slate-100 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-400">
            <p>© {{ date('Y') }} {{ $vendor }}. Tous droits réservés.</p>
            <p>{{ $platform }} est une solution <a href="{{ config('saas.vendor_url') }}" target="_blank" rel="noopener" class="font-bold text-slate-500 hover:text-orange-600">{{ $vendor }}</a></p>
        </div>
    </div>
</footer>

<script>
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Maquette du hero : une étape de validation se termine, une notification arrive, un document est ajouté
    function heroDemo() {
        return {
            phase: 0,
            init() {
                if (reduceMotion) { this.phase = 2; return; }
                setInterval(() => this.phase = (this.phase + 1) % 4, 2300);
            },
        };
    }

    // Saisie automatique dans la carte « Recherche »
    function typer(words) {
        return {
            text: '', done: false,
            init() {
                if (reduceMotion) { this.text = words[0]; this.done = true; return; }
                let w = 0, c = 0, deleting = false;
                const tick = () => {
                    const word = words[w];
                    if (!deleting) {
                        c++;
                        if (c >= word.length) { this.text = word; this.done = true; deleting = true; return setTimeout(tick, 1600); }
                    } else {
                        this.done = false;
                        c--;
                        if (c <= 0) { deleting = false; w = (w + 1) % words.length; }
                    }
                    this.text = word.slice(0, c);
                    setTimeout(tick, deleting ? 35 : 80);
                };
                setTimeout(tick, 600);
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Apparition progressive au défilement
        const items = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) { items.forEach(el => el.classList.add('is-visible')); }
        else {
            const io = new IntersectionObserver(entries => entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
            }), { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            items.forEach(el => io.observe(el));
        }

        // Compteurs animés
        const counters = document.querySelectorAll('[data-count]');
        const countIo = new IntersectionObserver(entries => entries.forEach(e => {
            if (!e.isIntersecting) return;
            countIo.unobserve(e.target);
            const el = e.target, target = +el.dataset.count;
            if (reduceMotion) return;
            const start = performance.now(), dur = 1600;
            const step = now => {
                const p = Math.min(1, (now - start) / dur), eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(target * eased).toLocaleString('fr-FR');
                if (p < 1) requestAnimationFrame(step);
            };
            el.textContent = '0';
            requestAnimationFrame(step);
        }), { threshold: 0.5 });
        counters.forEach(el => countIo.observe(el));

        // Halo qui suit le pointeur sur les cartes
        document.querySelectorAll('.spot').forEach(el => el.addEventListener('pointermove', e => {
            const r = el.getBoundingClientRect();
            el.style.setProperty('--x', (e.clientX - r.left) + 'px');
            el.style.setProperty('--y', (e.clientY - r.top) + 'px');
        }));

        // Hero : projecteur + inclinaison 3D de la maquette
        const hero = document.getElementById('hero'), tilt = document.getElementById('tilt');
        if (!reduceMotion && matchMedia('(pointer: fine)').matches) {
            hero.addEventListener('pointermove', e => {
                const r = hero.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
                hero.style.setProperty('--mx', (x * 100) + '%');
                hero.style.setProperty('--my', (y * 100) + '%');
                tilt.style.transform = `perspective(1400px) rotateX(${(0.5 - y) * 7}deg) rotateY(${(x - 0.5) * 9}deg)`;
            });
            hero.addEventListener('pointerleave', () => tilt.style.transform = '');
        }

        // Défilement : barre de lecture, étapes, lien actif du menu
        const progress = document.getElementById('progress'), steps = document.getElementById('steps');
        const stepItems = steps.querySelectorAll('[data-step]');
        const onScroll = () => {
            const max = document.documentElement.scrollHeight - innerHeight;
            progress.style.transform = `scaleX(${max > 0 ? scrollY / max : 0})`;
            const r = steps.getBoundingClientRect();
            const p = Math.min(1, Math.max(0, (innerHeight * 0.8 - r.top) / (r.height + innerHeight * 0.25)));
            steps.style.setProperty('--p', p);
            stepItems.forEach((el, i) => el.classList.toggle('on', p >= i / (stepItems.length - 1) * 0.92));
        };
        addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        const navLinks = document.querySelectorAll('[data-nav]');
        const navIo = new IntersectionObserver(entries => entries.forEach(e => {
            if (!e.isIntersecting) return;
            navLinks.forEach(a => {
                const on = a.dataset.nav === e.target.id;
                a.classList.toggle('!text-orange-600', on);
                a.classList.toggle('bg-orange-50', on);
            });
        }), { rootMargin: '-45% 0px -50% 0px' });
        navLinks.forEach(a => { const s = document.getElementById(a.dataset.nav); if (s) navIo.observe(s); });

        // Après envoi du formulaire (succès ou erreurs), ramener le visiteur sur la section contact
        @if(session('demo_success') || $errors->any())
            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
        @endif
    });
</script>
</body>
</html>
