<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .55s cubic-bezier(.22,1,.36,1) forwards; }
        .d1{animation-delay:.05s;opacity:0} .d2{animation-delay:.12s;opacity:0}
        .d3{animation-delay:.19s;opacity:0} .d4{animation-delay:.26s;opacity:0}
        .input-field {
            width:100%; background:#f8fafc; border:1.5px solid #e2e8f0;
            border-radius:14px; padding:13px 16px 13px 44px;
            font-size:14px; font-weight:600; color:#1e293b;
            transition:all .2s ease; outline:none;
        }
        .input-field::placeholder { color:#94a3b8; font-weight:500; }
        .input-field:focus { background:#fff; border-color:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,.1); }
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
    </style>
</head>
<body class="min-h-screen bg-slate-50 antialiased flex items-center justify-center px-4 py-12">

    {{-- Fond décoratif --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-orange-100/60 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-slate-100/80 rounded-full blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-[400px]">

        {{-- Header --}}
        <div class="text-center mb-8 fade-up d1">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-600 transition-colors text-xs font-bold mb-6">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour à la connexion
            </a>
            <div class="w-16 h-16 bg-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-xl shadow-orange-200">
                <i class="fa-solid fa-key text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Mot de passe oublié ?</h1>
            <p class="text-sm text-slate-400 font-medium mt-2 leading-relaxed max-w-xs mx-auto">
                Pas de panique. Entrez votre email et nous vous enverrons un lien de réinitialisation.
            </p>
        </div>

        {{-- Succès --}}
        @if(session('success'))
        <div class="mb-5 fade-up d1 flex items-start gap-3 bg-green-50 border border-green-200 rounded-2xl px-4 py-4">
            <div class="w-8 h-8 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-paper-plane text-green-600 text-sm"></i>
            </div>
            <div>
                <p class="text-xs font-black text-green-800">Email envoyé !</p>
                <p class="text-xs text-green-700 mt-0.5 leading-relaxed">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        {{-- Card formulaire --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-100/80 p-7 fade-up d2">

            <form action="{{ route('password.send') }}" method="POST" class="space-y-5"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Adresse email
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               placeholder="nom@groupebama.com"
                               class="input-field {{ $errors->has('email') ? 'border-red-300 bg-red-50' : '' }}">
                    </div>
                    @error('email')
                    <p class="text-red-500 text-[9px] font-bold mt-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation text-[8px]"></i>{{ $message }}
                    </p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary" :disabled="loading">
                    <span x-show="!loading" class="flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane text-[11px]"></i>
                        Envoyer le lien
                    </span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
                        Envoi en cours...
                    </span>
                </button>

            </form>

            {{-- Info --}}
            <div class="mt-5 flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-xl px-3.5 py-3">
                <i class="fa-solid fa-lightbulb text-amber-500 text-sm shrink-0 mt-0.5"></i>
                <p class="text-[10px] text-amber-800 font-medium leading-relaxed">
                    Vérifiez votre dossier spam si vous ne recevez pas l'email dans les 5 minutes.
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6 fade-up d3">
            <p class="text-[9px] text-slate-300 font-medium">
                Développé par <span class="text-slate-400 font-bold">Imperis Sarl</span> · Bamako {{ date('Y') }}
            </p>
        </div>

    </div>
</body>
</html>
