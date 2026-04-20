<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Plus Jakarta Sans', -apple-system, sans-serif; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .fade-up { animation: fadeUp .55s cubic-bezier(.22,1,.36,1) forwards; }
        .d1{animation-delay:.05s;opacity:0} .d2{animation-delay:.12s;opacity:0}
        .d3{animation-delay:.19s;opacity:0}
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
        .strength-bar { height:3px; border-radius:99px; flex:1; transition:all .3s ease; }
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
            <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-xl shadow-slate-200">
                <i class="fa-solid fa-lock-open text-orange-500 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Nouveau mot de passe</h1>
            <p class="text-sm text-slate-400 font-medium mt-2 leading-relaxed max-w-xs mx-auto">
                Choisissez un mot de passe fort pour sécuriser votre compte.
            </p>
        </div>

        {{-- Card formulaire --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-xl shadow-slate-100/80 p-7 fade-up d2"
             x-data="{
                showNew: false,
                showConfirm: false,
                loading: false,
                password: '',
                strength: 0,
                strengthLabel: '',
                strengthColor: '',
                checkStrength(v) {
                    let s = 0;
                    if (v.length >= 8)         s++;
                    if (/[A-Z]/.test(v))       s++;
                    if (/[0-9]/.test(v))       s++;
                    if (/[^A-Za-z0-9]/.test(v)) s++;
                    this.strength = s;
                    const labels = ['','Faible','Moyen','Bon','Fort'];
                    const colors = ['','text-red-500','text-amber-500','text-blue-500','text-green-500'];
                    this.strengthLabel = labels[s] || '';
                    this.strengthColor = colors[s] || '';
                }
             }">

            <form action="{{ route('password.update') }}" method="POST" class="space-y-5"
                  @submit="loading = true">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                {{-- Nouveau mot de passe --}}
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Nouveau mot de passe
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                        <input :type="showNew ? 'text' : 'password'" name="password" required
                               placeholder="Min. 8 caractères"
                               x-model="password"
                               @input="checkStrength($event.target.value)"
                               class="input-field pr-12 {{ $errors->has('password') ? 'border-red-300 bg-red-50' : '' }}">
                        <button type="button" @click="showNew = !showNew"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-600 transition-colors p-1">
                            <i class="fa-solid text-xs" :class="showNew ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>

                    {{-- Barre de force --}}
                    <div x-show="password.length > 0" class="mt-2.5 space-y-1.5">
                        <div class="flex gap-1.5">
                            <template x-for="i in 4" :key="i">
                                <div class="strength-bar"
                                     :class="i <= strength
                                        ? (strength === 1 ? 'bg-red-400' : strength === 2 ? 'bg-amber-400' : strength === 3 ? 'bg-blue-400' : 'bg-green-500')
                                        : 'bg-slate-100'"></div>
                            </template>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-[9px] font-black" :class="strengthColor" x-text="strengthLabel"></p>
                            <p class="text-[9px] text-slate-300 font-medium">
                                <span x-text="password.length"></span> caractères
                            </p>
                        </div>
                    </div>

                    @error('password')
                    <p class="text-red-500 text-[9px] font-bold mt-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation text-[8px]"></i>{{ $message }}
                    </p>
                    @enderror
                </div>

                {{-- Confirmation --}}
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                        Confirmer le mot de passe
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-lock-open absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" required
                               placeholder="Répétez le mot de passe"
                               class="input-field pr-12">
                        <button type="button" @click="showConfirm = !showConfirm"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-600 transition-colors p-1">
                            <i class="fa-solid text-xs" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                {{-- Règles --}}
                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Règles du mot de passe</p>
                    <div class="space-y-1.5">
                        @foreach([
                            ['password.length >= 8', 'Au moins 8 caractères'],
                            ['/[A-Z]/.test(password)', 'Une lettre majuscule'],
                            ['/[0-9]/.test(password)', 'Un chiffre'],
                            ['/[^A-Za-z0-9]/.test(password)', 'Un caractère spécial'],
                        ] as [$check, $label])
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full flex items-center justify-center shrink-0 transition-colors"
                                 :class="{{ $check }} ? 'bg-green-100' : 'bg-slate-100'">
                                <i class="fa-solid text-[7px] transition-colors"
                                   :class="{{ $check }} ? 'fa-check text-green-600' : 'fa-minus text-slate-300'"></i>
                            </div>
                            <span class="text-[10px] font-medium transition-colors"
                                  :class="{{ $check }} ? 'text-green-700' : 'text-slate-400'">{{ $label }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                @error('token')
                <div class="flex items-center gap-2 bg-red-50 border border-red-100 rounded-xl px-3 py-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 text-xs shrink-0"></i>
                    <p class="text-xs font-bold text-red-700">{{ $message }}</p>
                </div>
                @enderror

                <button type="submit" class="btn-primary" :disabled="loading || strength < 2">
                    <span x-show="!loading" class="flex items-center gap-2">
                        <i class="fa-solid fa-check text-[11px]"></i>
                        Réinitialiser le mot de passe
                    </span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <i class="fa-solid fa-spinner fa-spin text-[11px]"></i>
                        Mise à jour...
                    </span>
                </button>

            </form>
        </div>

        {{-- Retour --}}
        <div class="text-center mt-5 fade-up d3">
            <a href="{{ route('login') }}" class="text-xs font-bold text-slate-400 hover:text-orange-600 transition-colors">
                <i class="fa-solid fa-arrow-left mr-1 text-[9px]"></i> Retour à la connexion
            </a>
        </div>

    </div>
</body>
</html>
