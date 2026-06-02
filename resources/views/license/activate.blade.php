<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activer la Licence — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .55s cubic-bezier(.22,1,.36,1) forwards; }
        .d1{animation-delay:.05s;opacity:0} .d2{animation-delay:.15s;opacity:0}
        .d3{animation-delay:.25s;opacity:0} .d4{animation-delay:.35s;opacity:0}
        .input-field {
            width:100%; background:#f8fafc; border:1.5px solid #e2e8f0;
            border-radius:14px; padding:13px 16px 13px 44px;
            font-size:14px; font-weight:600; color:#1e293b;
            transition: all .2s ease; outline:none;
            letter-spacing: 0.05em; text-transform: uppercase;
        }
        .input-field::placeholder { color:#94a3b8; font-weight:500; text-transform:none; letter-spacing:normal; }
        .input-field:focus { background:#fff; border-color:#f97316; box-shadow:0 0 0 4px rgba(249,115,22,.1); }
        .input-field.error { background:#fff5f5; border-color:#fca5a5; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-50 to-slate-100 antialiased">

<div class="min-h-screen flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 p-10">

            {{-- Header --}}
            <div class="fade-up d1 text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-orange-50 border border-orange-100 mb-4">
                    <i class="fa-solid fa-key text-orange-600 text-xl"></i>
                </div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight mb-1">Activation de Licence</h1>
                <p class="text-xs text-slate-400 font-medium">
                    Entrez la clé fournie par <strong class="text-slate-600">Imperis SARL</strong>
                </p>
            </div>

            {{-- Erreurs --}}
            @if($errors->any())
            <div class="fade-up d1 mb-6 flex items-start gap-3 bg-red-50 border border-red-100 rounded-2xl px-4 py-3.5">
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

            {{-- Succès --}}
            @if(session('success'))
            <div class="fade-up d1 mb-6 flex items-start gap-3 bg-green-50 border border-green-100 rounded-2xl px-4 py-3.5">
                <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fa-solid fa-check text-green-600 text-xs"></i>
                </div>
                <p class="text-xs font-bold text-green-700 leading-relaxed">{{ session('success') }}</p>
            </div>
            @endif

            {{-- Formulaire --}}
            <form action="{{ route('license.activate.submit') }}" method="POST" class="space-y-5"
                  x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="fade-up d2">
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Clé de licence
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                        <input type="text" name="license_key" required
                               placeholder="IMPERIS-XXXX-XXXX-XXXX-EXP20270601"
                               value="{{ old('license_key') }}"
                               class="input-field {{ $errors->has('license_key') ? 'error' : '' }}">
                    </div>
                    <p class="text-[9px] text-slate-400 font-medium mt-2">
                        Format : IMPERIS-XXXX-XXXX-XXXX-EXPYYYYMMDD
                    </p>
                </div>

                {{-- Info licence actuelle --}}
                @if($license)
                <div class="fade-up d3 bg-slate-50 border border-slate-100 rounded-xl p-4">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Licence actuelle</p>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">{{ Str::limit($license->license_key, 20) }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $license->isExpired() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                            {{ $license->isExpired() ? 'Expirée' : 'Active' }}
                        </span>
                    </div>
                </div>
                @endif

                {{-- Bouton --}}
                <div class="fade-up d4">
                    <button type="submit" :disabled="loading"
                            class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-600 to-orange-500 text-white px-6 py-3.5 rounded-xl font-bold text-sm shadow-lg shadow-orange-200 hover:shadow-xl hover:shadow-orange-200 transition-all hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span x-show="!loading">
                            <i class="fa-solid fa-check-circle text-xs mr-1"></i>
                            Activer la licence
                        </span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                            Vérification...
                        </span>
                    </button>
                </div>
            </form>

            {{-- Retour --}}
            <div class="fade-up d4 text-center mt-6">
                <a href="{{ route('license.expired') }}" class="text-[11px] font-bold text-slate-400 hover:text-orange-600 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Retour
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6">
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">
                Licence émise exclusivement par Imperis SARL · Bamako {{ date('Y') }}
            </p>
        </div>

    </div>
</div>

</body>
</html>
