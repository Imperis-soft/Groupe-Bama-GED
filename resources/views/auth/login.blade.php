<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up { animation: fade-up 0.5s ease-out forwards; }
    </style>
</head>
<body class="h-full bg-white antialiased">

<div class="flex h-full min-h-screen">

    {{-- Panneau gauche (branding) --}}
    <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 bg-orange-600 relative flex-col items-center justify-center p-16 overflow-hidden">

        {{-- Grille décorative --}}
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <svg width="100%" height="100%">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>
        </div>

        {{-- Blobs --}}
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-orange-800/20 rounded-full blur-3xl"></div>

        <div class="relative z-10 max-w-lg">
            {{-- Logo --}}
            <div class="mb-10 inline-flex items-center justify-center w-20 h-20 bg-white rounded-[2rem] shadow-2xl shadow-orange-900/20 rotate-6 hover:rotate-0 transition-transform duration-500">
                <i class="fa-solid fa-file-shield text-orange-600 text-4xl"></i>
            </div>

            <h1 class="text-5xl xl:text-6xl font-black text-white leading-tight mb-6">
                GED<br><span class="text-orange-200">Groupe Bama</span>
            </h1>

            <p class="text-orange-100 text-lg font-medium leading-relaxed opacity-90">
                Gestion Électronique de Documents sécurisée.<br>
                Archivage numérique, signatures, workflows d'approbation et édition native Microsoft Word.
            </p>

            {{-- Features --}}
            <div class="mt-10 space-y-3">
                @foreach([
                    ['fa-shield-check', 'Archivage sécurisé avec vérification d\'intégrité'],
                    ['fa-signature',    'Signatures numériques et workflows d\'approbation'],
                    ['fa-magnifying-glass', 'Recherche full-text en français'],
                    ['fa-qrcode',       'QR codes de vérification sur chaque document'],
                ] as [$icon, $text])
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $icon }} text-white text-xs"></i>
                    </div>
                    <p class="text-sm text-orange-100 font-medium">{{ $text }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="absolute bottom-8 left-16 text-white/30 text-[9px] font-black tracking-[0.3em] uppercase">
            © {{ date('Y') }} Groupe Bama — Solution Imperis Sarl
        </div>
    </div>

    {{-- Panneau droit (formulaire) --}}
    <div class="w-full lg:w-1/2 xl:w-2/5 flex items-center justify-center p-8 sm:p-12 lg:p-16 bg-white">
        <div class="w-full max-w-sm fade-up">

            {{-- Logo mobile --}}
            <div class="lg:hidden flex items-center gap-3 mb-10">
                <div class="w-10 h-10 rounded-xl bg-orange-600 flex items-center justify-center shadow-lg shadow-orange-200">
                    <i class="fa-solid fa-file-shield text-white text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900 leading-none">Groupe Bama</p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">GED Platform</p>
                </div>
            </div>

            <div class="mb-8">
                <h2 class="text-3xl font-black text-slate-900 tracking-tight leading-none">Connexion</h2>
                <p class="text-sm text-slate-400 font-medium mt-2">Entrez vos identifiants pour accéder à la plateforme.</p>
            </div>

            {{-- Erreurs --}}
            @if($errors->any())
            <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-100 rounded-2xl px-4 py-3">
                <i class="fa-solid fa-circle-exclamation text-red-500 text-sm mt-0.5 shrink-0"></i>
                <div>
                    @foreach($errors->all() as $error)
                    <p class="text-xs font-bold text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Email professionnel
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="nom@groupebama.com"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-10 pr-4 py-3 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all @error('email') border-red-300 bg-red-50 @enderror">
                    </div>
                </div>

                <div x-data="{ show: false }">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Mot de passe
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        <input :type="show ? 'text' : 'password'" name="password" required
                               placeholder="••••••••"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-10 pr-11 py-3 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all">
                        <button type="button" @click="show = !show"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors">
                            <i class="fa-solid text-xs" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-orange-600 border-slate-300 rounded focus:ring-orange-500">
                        <span class="text-xs font-medium text-slate-500">Rester connecté</span>
                    </label>
                    <a href="{{ route('password.forgot') }}" class="text-xs font-bold text-orange-600 hover:underline">
                        Mot de passe oublié ?
                    </a>
                </div>

                <button type="submit"
                    class="w-full bg-orange-600 hover:bg-orange-500 active:scale-[0.98] text-white py-3.5 rounded-xl font-black text-sm uppercase tracking-widest shadow-xl shadow-orange-200 transition-all flex items-center justify-center gap-3">
                    Se connecter
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Besoin d'aide ?
                    <a href="mailto:contact@imperis.com" class="text-orange-600 hover:underline ml-1">Contacter l'IT</a>
                </p>
            </div>

        </div>
    </div>

</div>

</body>
</html>
