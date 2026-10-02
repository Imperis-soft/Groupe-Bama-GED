<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — {{ config('saas.platform_name') }}</title>
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
        .dots      { background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,.08) 1px, transparent 0); background-size: 26px 26px; }
        .dots-dark { background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.07) 1px, transparent 0); background-size: 22px 22px; }
        .accent    { background: linear-gradient(120deg, #ea580c 0%, #f97316 55%, #fb923c 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .fade-b    { mask-image: linear-gradient(to bottom, #000 20%, transparent); -webkit-mask-image: linear-gradient(to bottom, #000 20%, transparent); }

        @keyframes rise  { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .rise  { opacity: 0; animation: rise .6s cubic-bezier(.22,1,.36,1) forwards; }
        .float { animation: float 6s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) { .rise { animation: none; opacity: 1; } .float { animation: none; } }

        @layer components {
            .label { @apply block text-[13px] font-semibold text-slate-700 mb-2; }
            .field { @apply block w-full h-12 rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-[15px] text-slate-900 placeholder:text-slate-400 transition hover:border-slate-300 focus:outline-none focus:border-orange-400 focus:ring-4 focus:ring-orange-500/10; }
            .field-icon { @apply absolute left-4 top-1/2 -translate-y-1/2 text-[13px] text-slate-400 pointer-events-none transition; }
            .btn { @apply inline-flex items-center justify-center gap-2 h-12 px-5 rounded-xl text-[15px] font-bold whitespace-nowrap transition focus:outline-none focus-visible:ring-4; }
            .btn-primary { @apply bg-orange-600 text-white shadow-cta hover:bg-orange-700 hover:-translate-y-px focus-visible:ring-orange-500/25 disabled:opacity-70 disabled:hover:translate-y-0 disabled:cursor-wait; }
        }
    </style>
</head>
<body class="min-h-full bg-white font-sans text-slate-700 antialiased">

<div class="min-h-screen grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">

    {{-- ===================== FORMULAIRE ===================== --}}
    <div class="relative flex flex-col overflow-hidden">
        <div class="absolute inset-x-0 top-0 h-80 dots fade-b pointer-events-none"></div>
        <div class="absolute -top-40 -left-24 w-[420px] h-[320px] rounded-full bg-orange-400/15 blur-3xl pointer-events-none"></div>

        {{-- Barre du haut : logo + retour au site --}}
        <header class="relative flex items-center justify-between gap-4 px-6 sm:px-10 h-20">
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <x-logo class="w-11 h-11 transition group-hover:-rotate-6" />
                <span class="leading-none">
                    <span class="block text-[17px] font-extrabold tracking-tight text-slate-900">{{ config('saas.platform_name') }}</span>
                    <span class="block text-[11px] font-medium text-slate-500 mt-1">par {{ config('saas.vendor_name') }}</span>
                </span>
            </a>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full border border-slate-200 bg-white/80 backdrop-blur text-[13px] font-semibold text-slate-600 hover:text-orange-600 hover:border-orange-200 transition">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Retour au site</span>
            </a>
        </header>

        {{-- Contenu --}}
        <main class="relative flex-1 flex items-center justify-center px-6 sm:px-10 py-10">
            <div class="w-full max-w-[400px]">

                <div class="rise" style="animation-delay:.05s">
                    <p class="inline-flex items-center gap-2 text-[13px] font-semibold text-orange-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Espace client
                    </p>
                    <h1 class="mt-3 text-[34px] sm:text-[38px] leading-[1.08] font-extrabold tracking-[-0.03em] text-slate-900">
                        Content de vous <span class="accent">revoir.</span>
                    </h1>
                    <p class="mt-3 text-[15px] text-slate-500 leading-relaxed">
                        Connectez-vous pour retrouver vos documents, vos validations et vos signatures.
                    </p>
                </div>

                {{-- Messages --}}
                @if($errors->any())
                    <div class="rise mt-7 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50/70 px-4 py-3.5" style="animation-delay:.1s" role="alert">
                        <i class="fa-solid fa-circle-exclamation text-red-500 mt-0.5"></i>
                        <div class="text-sm text-red-700 space-y-0.5">
                            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                        </div>
                    </div>
                @endif

                @if(session('success'))
                    <div class="rise mt-7 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3.5" style="animation-delay:.1s" role="status">
                        <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5"></i>
                        <p class="text-sm text-emerald-800">{{ session('success') }}</p>
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="mt-8 space-y-5"
                      x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div class="rise" style="animation-delay:.12s">
                        <label for="email" class="label">Adresse email</label>
                        <div class="relative group">
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                   placeholder="nom@entreprise.com"
                                   class="field peer {{ $errors->has('email') ? '!border-red-300 !bg-red-50/40' : '' }}">
                            <i class="fa-regular fa-envelope field-icon peer-focus:text-orange-500"></i>
                        </div>
                    </div>

                    <div class="rise" style="animation-delay:.18s" x-data="{ show: false }">
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="label !mb-0">Mot de passe</label>
                            <a href="{{ route('password.forgot') }}" class="text-[13px] font-semibold text-orange-600 hover:text-orange-700 transition">Mot de passe oublié ?</a>
                        </div>
                        <div class="relative">
                            <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                   placeholder="Votre mot de passe" class="field peer !pr-12">
                            <i class="fa-solid fa-lock field-icon peer-focus:text-orange-500"></i>
                            <button type="button" @click="show = !show"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                    :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'">
                                <i class="fa-regular text-[13px]" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <label class="rise flex items-center gap-3 cursor-pointer select-none w-fit" style="animation-delay:.24s">
                        <input type="checkbox" name="remember" class="sr-only peer" @checked(old('remember'))>
                        <span class="relative w-10 h-6 rounded-full bg-slate-200 transition peer-checked:bg-orange-600 peer-focus-visible:ring-4 peer-focus-visible:ring-orange-500/20
                                     after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-4"></span>
                        <span class="text-sm font-medium text-slate-600">Rester connecté</span>
                    </label>

                    <div class="rise pt-1" style="animation-delay:.3s">
                        <button type="submit" class="btn btn-primary w-full" :disabled="loading">
                            <span x-show="!loading" class="inline-flex items-center gap-2">Se connecter <i class="fa-solid fa-arrow-right text-[13px]"></i></span>
                            <span x-show="loading" x-cloak class="inline-flex items-center gap-2"><i class="fa-solid fa-circle-notch fa-spin text-[13px]"></i> Connexion…</span>
                        </button>
                    </div>
                </form>

                <div class="rise mt-8 rounded-2xl border border-slate-200/70 bg-slate-50/60 px-5 py-4 flex items-center gap-4" style="animation-delay:.36s">
                    <span class="w-10 h-10 rounded-xl bg-white ring-1 ring-slate-200/70 shadow-soft flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-building text-orange-600 text-sm"></i>
                    </span>
                    <p class="text-sm text-slate-600 leading-snug">
                        Pas encore client ?
                        <a href="{{ url('/') }}#contact" class="font-semibold text-orange-600 hover:text-orange-700">Demander une démo</a>
                    </p>
                </div>
            </div>
        </main>

        <footer class="relative px-6 sm:px-10 pb-6 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
            <p>© {{ date('Y') }} {{ config('saas.vendor_name') }}</p>
            <a href="mailto:{{ config('saas.support_email') }}" class="hover:text-orange-600 transition">
                <i class="fa-regular fa-life-ring mr-1"></i> Besoin d'aide ? {{ config('saas.support_email') }}
            </a>
        </footer>
    </div>

    {{-- ===================== PANNEAU VISUEL ===================== --}}
    <aside class="hidden lg:block p-3 lg:sticky lg:top-0 lg:h-screen">
        <div class="relative h-full overflow-hidden rounded-[28px] bg-ink text-white">
            <div class="absolute inset-0 dots-dark"></div>
            <div class="absolute -top-32 -right-24 w-[520px] h-[420px] rounded-full bg-orange-500/30 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-20 w-[420px] h-[360px] rounded-full bg-orange-600/20 blur-3xl"></div>

            <div class="relative h-full flex flex-col justify-between p-12 xl:p-14">
                <div class="inline-flex items-center gap-2 self-start rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-[12px] font-semibold text-slate-300 backdrop-blur">
                    <span class="relative flex w-2 h-2"><span class="absolute inline-flex w-full h-full rounded-full bg-emerald-400 opacity-60 animate-ping"></span><span class="relative w-2 h-2 rounded-full bg-emerald-400"></span></span>
                    Tous les services sont opérationnels
                </div>

                {{-- Maquette produit --}}
                <div class="relative mx-auto w-full max-w-[460px] my-10 [@media(max-height:780px)]:scale-90 [@media(max-height:780px)]:my-4">
                    <div class="float rounded-2xl bg-white text-slate-700 shadow-pop ring-1 ring-white/10 overflow-hidden">
                        <div class="flex items-center gap-1.5 px-4 h-10 border-b border-slate-100">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-300"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-300"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-300"></span>
                            <span class="ml-3 text-[11px] text-slate-400"><i class="fa-solid fa-lock text-[9px] text-emerald-500 mr-1"></i>{{ strtolower(config('saas.platform_name')) }} · Documents</span>
                        </div>
                        <div class="p-4 space-y-1">
                            @foreach([
                                ['fa-file-word text-sky-500', 'Contrat cadre fournisseur', 'ACME-7K2Q9D · v3', 'Approuvé', 'bg-emerald-50 text-emerald-700'],
                                ['fa-file-pdf text-red-500', 'Procès-verbal du conseil', 'ACME-P4M1XZ · v2', 'En révision', 'bg-amber-50 text-amber-700'],
                                ['fa-file-excel text-emerald-600', 'Rapport financier T3', 'ACME-F2N7WC · v5', 'Archivé', 'bg-violet-50 text-violet-700'],
                            ] as [$icon, $title, $ref, $status, $badge])
                                <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ $loop->first ? 'bg-orange-50/60' : '' }}">
                                    <span class="w-9 h-9 rounded-lg bg-white ring-1 ring-slate-100 shadow-soft flex items-center justify-center"><i class="fa-solid {{ $icon }} text-sm"></i></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[13px] font-bold text-slate-900 truncate">{{ $title }}</p>
                                        <p class="text-[10px] font-mono text-slate-400">{{ $ref }}</p>
                                    </div>
                                    <span class="rounded-full px-2 py-1 text-[10px] font-bold {{ $badge }}">{{ $status }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Badge « authentique » --}}
                    <div class="absolute -top-6 -right-6 xl:-right-10 flex items-center gap-3 rounded-2xl bg-ink/90 backdrop-blur px-4 py-3 ring-1 ring-white/10 shadow-pop">
                        <span class="w-9 h-9 rounded-lg bg-white flex items-center justify-center"><i class="fa-solid fa-qrcode text-ink"></i></span>
                        <div>
                            <p class="text-[12px] font-bold">Document authentique</p>
                            <p class="text-[11px] text-emerald-400"><i class="fa-solid fa-shield-halved mr-1"></i>Empreinte vérifiée</p>
                        </div>
                    </div>

                    {{-- Carte workflow --}}
                    <div class="absolute -bottom-10 -left-6 xl:-left-10 w-60 rounded-2xl bg-white text-slate-700 p-4 shadow-pop ring-1 ring-slate-900/5">
                        <div class="flex items-center justify-between text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-400">
                            <span>Workflow</span><span class="font-mono">2/3</span>
                        </div>
                        <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden"><span class="block h-full w-2/3 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500"></span></div>
                        <ul class="mt-3 space-y-2 text-[12px] font-medium">
                            <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-500"></i> Direction juridique</li>
                            <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-500"></i> Direction financière</li>
                            <li class="flex items-center gap-2"><i class="fa-solid fa-hourglass-half text-amber-500"></i> Direction générale</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="text-[30px] xl:text-[34px] leading-[1.1] font-extrabold tracking-[-0.03em]">
                        Chaque document,<br><span class="text-orange-400">maîtrisé de bout en bout.</span>
                    </h2>
                    <div class="mt-6 grid grid-cols-3 gap-3 max-w-[520px] [@media(max-height:780px)]:hidden">
                        @foreach([
                            ['fa-code-branch', 'Versions conservées'],
                            ['fa-signature', 'Signature et QR code'],
                            ['fa-clock-rotate-left', 'Traçabilité complète'],
                        ] as [$icon, $text])
                            <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3.5">
                                <i class="fa-solid {{ $icon }} text-orange-400 text-sm"></i>
                                <p class="mt-2 text-[13px] font-semibold text-slate-200 leading-snug">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>

</body>
</html>
