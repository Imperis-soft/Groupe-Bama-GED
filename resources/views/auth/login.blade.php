<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        @keyframes slideIn { from{opacity:0;transform:translateX(-20px)} to{opacity:1;transform:translateX(0)} }
        @keyframes pulse-ring { 0%{transform:scale(1);opacity:.6} 100%{transform:scale(1.5);opacity:0} }
        @keyframes float { 0%,100%{transform:translateY(0) rotate(6deg)} 50%{transform:translateY(-8px) rotate(6deg)} }
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
        .fade-up { animation: fadeUp .55s cubic-bezier(.22,1,.36,1) forwards; }
        .slide-in { animation: slideIn .6s cubic-bezier(.22,1,.36,1) forwards; }
        .d1{animation-delay:.05s;opacity:0} .d2{animation-delay:.12s;opacity:0}
        .d3{animation-delay:.19s;opacity:0} .d4{animation-delay:.26s;opacity:0}
        .d5{animation-delay:.33s;opacity:0} .d6{animation-delay:.40s;opacity:0}
        .logo-float { animation: float 4s ease-in-out infinite; }
        .pulse-ring::before {
            content:''; position:absolute; inset:-6px; border-radius:inherit;
            border:2px solid rgba(255,255,255,.3);
            animation: pulse-ring 2s ease-out infinite;
        }
        .input-field {
            width:100%; background:#f8fafc; border:1.5px solid #e2e8f0;
            border-radius:14px; padding:13px 16px 13px 44px;
            font-size:14px; font-weight:600; color:#1e293b;
            transition: all .2s ease; outline:none;
        }
        .input-field::placeholder { color:#94a3b8; font-weight:500; }
        .input-field:focus { background:#fff; border-color:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,.1); }
        .input-field.error { background:#fff5f5; border-color:#fca5a5; }
        .btn-primary {
            width:100%; background:linear-gradient(135deg,#ea580c,#f97316);
            color:#fff; padding:14px; border-radius:14px;
            font-size:13px; font-weight:900; letter-spacing:.08em; text-transform:uppercase;
            border:none; cursor:pointer; transition:all .2s ease;
            box-shadow:0 8px 24px -4px rgba(234,88,12,.45);
            display:flex; align-items:center; justify-content:center; gap:10px;
        }
        .btn-primary:hover { transform:translateY(-1px); box-shadow:0 12px 32px -4px rgba(234,88,12,.55); }
        .btn-primary:active { transform:scale(.98); }
        .btn-primary:disabled { opacity:.7; cursor:not-allowed; transform:none; }
        .left-panel {
            background: linear-gradient(145deg, #c2410c 0%, #ea580c 40%, #f97316 100%);
        }
        .grid-svg { opacity:.08; }
        .feature-item { transition: transform .2s ease; }
        .feature-item:hover { transform: translateX(4px); }
        .divider { display:flex; align-items:center; gap:12px; }
        .divider::before,.divider::after { content:''; flex:1; height:1px; background:#e2e8f0; }
        .strength-bar { height:3px; border-radius:99px; transition:all .3s ease; }
    </style>
</head>
<body class="h-full bg-white antialiased overflow-x-hidden">

<div class="flex min-h-screen">

    {{-- ===== PANNEAU GAUCHE — BRANDING ===== --}}
    <div class="hidden lg:flex lg:w-[52%] xl:w-[55%] left-panel relative flex-col justify-between p-12 xl:p-16 overflow-hidden">

        {{-- Grille SVG --}}
        <svg class="grid-svg absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="g" width="48" height="48" patternUnits="userSpaceOnUse">
                    <path d="M 48 0 L 0 0 0 48" fill="none" stroke="white" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#g)"/>
        </svg>

        {{-- Blobs décoratifs --}}
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] bg-orange-900/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>

        {{-- Contenu --}}
        <div class="relative z-10">
            {{-- Logo --}}
            <div class="flex items-center gap-3 mb-16">
                <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center border border-white/30">
                    <i class="fa-solid fa-file-shield text-white text-base"></i>
                </div>
                <div>
                    <p class="text-white font-black text-sm leading-none tracking-tight">Groupe Bama</p>
                    <p class="text-orange-200 text-[9px] font-bold uppercase tracking-widest mt-0.5">GED Platform</p>
                </div>
            </div>

            {{-- Icône principale --}}
            <div class="relative inline-block mb-10">
                <div class="pulse-ring relative w-20 h-20 bg-white rounded-[1.75rem] flex items-center justify-center shadow-2xl shadow-orange-900/30 logo-float">
                    <i class="fa-solid fa-file-shield text-orange-600 text-4xl"></i>
                </div>
            </div>

            <h1 class="text-4xl xl:text-5xl font-black text-white leading-[1.05] tracking-tight mb-5">
                Votre archive<br>documentaire<br><span class="text-orange-200">sécurisée</span>
            </h1>

            <p class="text-orange-100/80 text-sm font-medium leading-relaxed max-w-sm mb-10">
                Gérez, approuvez, signez et partagez tous vos documents d'entreprise depuis une plateforme unique, traçable et conforme.
            </p>

            {{-- Features --}}
            <div class="space-y-3">
                @foreach([
                    ['fa-shield-halved',      'Contrôle d\'accès par rôle (RBAC + ACL)'],
                    ['fa-list-check',         'Workflows d\'approbation multi-étapes'],
                    ['fa-signature',          'Signatures numériques vérifiables'],
                    ['fa-clock-rotate-left',  'Journal d\'audit complet de chaque action'],
                    ['fa-qrcode',             'QR code d\'authenticité sur chaque document'],
                ] as [$icon, $text])
                <div class="feature-item flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $icon }} text-white text-[11px]"></i>
                    </div>
                    <p class="text-sm text-orange-50/90 font-medium">{{ $text }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Footer branding --}}
        <div class="relative z-10 flex items-center justify-between">
            <p class="text-white/30 text-[9px] font-black tracking-[0.25em] uppercase">
                © {{ date('Y') }} Groupe Bama
            </p>
            <div class="flex items-center gap-2 bg-white/10 border border-white/20 rounded-xl px-3 py-1.5">
                <div class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></div>
                <span class="text-white/70 text-[9px] font-bold uppercase tracking-wider">Système opérationnel</span>
            </div>
        </div>
    </div>

    {{-- ===== PANNEAU DROIT — FORMULAIRE ===== --}}
    <div class="w-full lg:w-[48%] xl:w-[45%] flex items-center justify-center px-6 py-12 sm:px-10 lg:px-12 xl:px-16 bg-white">
        <div class="w-full max-w-[380px]">

            {{-- Logo mobile --}}
            <div class="lg:hidden flex items-center gap-3 mb-10 fade-up d1">
                <div class="w-10 h-10 rounded-xl bg-orange-600 flex items-center justify-center shadow-lg shadow-orange-200">
                    <i class="fa-solid fa-file-shield text-white text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900 leading-none">Groupe Bama</p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">GED Platform</p>
                </div>
            </div>

            {{-- Titre --}}
            <div class="mb-8 fade-up d1">
                <p class="text-[10px] font-black text-orange-600 uppercase tracking-[0.3em] mb-2">Bienvenue</p>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight leading-none">Connexion</h2>
                <p class="text-sm text-slate-400 font-medium mt-2.5 leading-relaxed">
                    Entrez vos identifiants pour accéder à votre espace documentaire.
                </p>
            </div>

            {{-- Alerte erreur --}}
            @if($errors->any())
            <div class="mb-6 fade-up d1 flex items-start gap-3 bg-red-50 border border-red-100 rounded-2xl px-4 py-3.5">
                <div class="w-7 h-7 rounded-lg bg-red-100 flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 text-xs"></i>
                </div>
                <div>
                    @foreach($errors->all() as $error)
                    <p class="text-xs font-bold text-red-700 leading-relaxed">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Alerte succès (reset password) --}}
            @if(session('success'))
            <div class="mb-6 fade-up d1 flex items-start gap-3 bg-green-50 border border-green-100 rounded-2xl px-4 py-3.5">
                <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fa-solid fa-check text-green-600 text-xs"></i>
                </div>
                <p class="text-xs font-bold text-green-700 leading-relaxed">{{ session('success') }}</p>
            </div>
            @endif

            {{-- Formulaire --}}
            <form action="{{ route('login') }}" method="POST" class="space-y-5"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                {{-- Email --}}
                <div class="fade-up d2">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Adresse email
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="nom@groupebama.com"
                               class="input-field {{ $errors->has('email') ? 'error' : '' }}">
                    </div>
                    @error('email')
                    <p class="text-red-500 text-[9px] font-bold mt-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation text-[8px]"></i>{{ $message }}
                    </p>
                    @enderror
                </div>

                {{-- Mot de passe --}}
                <div class="fade-up d3" x-data="{ show: false }">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest">
                            Mot de passe
                        </label>
                        <a href="{{ route('password.forgot') }}"
                           class="text-[10px] font-bold text-orange-600 hover:text-orange-700 transition-colors hover:underline">
                            Oublié ?
                        </a>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                        <input :type="show ? 'text' : 'password'" name="password" required
                               placeholder="••••••••"
                               class="input-field pr-12">
                        <button type="button" @click="show = !show"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-600 transition-colors p-1">
                            <i class="fa-solid text-xs" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                {{-- Se souvenir --}}
                <div class="fade-up d4 flex items-center justify-between">
                    <label class="flex items-center gap-2.5 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" name="remember" id="remember"
                                   class="sr-only peer" {{ old('remember') ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-slate-200 peer-checked:bg-orange-600 rounded-full transition-colors duration-200 peer-focus:ring-2 peer-focus:ring-orange-500/30"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 group-hover:text-slate-700 transition-colors select-none">
                            Rester connecté
                        </span>
                    </label>
                </div>

                {{-- Bouton submit --}}
                <div class="fade-up d5">
                    <button type="submit" class="btn-primary" :disabled="loading">
                        <span x-show="!loading">
                            <i class="fa-solid fa-arrow-right-to-bracket text-[11px]"></i>
                            Se connecter
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
                            Connexion en cours...
                        </span>
                    </button>
                </div>

            </form>

            {{-- Séparateur --}}
            <div class="fade-up d5 my-7">
                <div class="divider">
                    <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest whitespace-nowrap">Accès sécurisé</span>
                </div>
            </div>

            {{-- Badges sécurité --}}
            <div class="fade-up d6 grid grid-cols-3 gap-2 mb-7">
                <div class="flex flex-col items-center gap-1.5 bg-slate-50 rounded-xl p-3 border border-slate-100">
                    <i class="fa-solid fa-shield-halved text-green-500 text-base"></i>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider text-center leading-tight">HTTPS<br>Chiffré</span>
                </div>
                <div class="flex flex-col items-center gap-1.5 bg-slate-50 rounded-xl p-3 border border-slate-100">
                    <i class="fa-solid fa-clock text-blue-500 text-base"></i>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider text-center leading-tight">Session<br>Sécurisée</span>
                </div>
                <div class="flex flex-col items-center gap-1.5 bg-slate-50 rounded-xl p-3 border border-slate-100">
                    <i class="fa-solid fa-fingerprint text-orange-500 text-base"></i>
                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider text-center leading-tight">Audit<br>Complet</span>
                </div>
            </div>

            {{-- Aide --}}
            <div class="fade-up d6 text-center">
                <p class="text-[10px] text-slate-400 font-medium">
                    Problème de connexion ?
                    <a href="mailto:contact@imperis.com" class="text-orange-600 font-bold hover:underline ml-1">
                        Contacter le support
                    </a>
                </p>
                <p class="text-[9px] text-slate-300 mt-2 font-medium">
                    Développé par <span class="text-slate-400 font-bold">Imperis Sarl</span> · Bamako {{ date('Y') }}
                </p>
            </div>

        </div>
    </div>

</div>

</body>
</html>
