<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-4">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-orange-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-orange-200">
            <i class="fa-solid fa-lock text-white text-xl"></i>
        </div>
        <h1 class="text-2xl font-black text-slate-900">Nouveau mot de passe</h1>
        <p class="text-sm text-slate-400 mt-2">Choisissez un mot de passe sécurisé.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div x-data="{ show: false }">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nouveau mot de passe</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input :type="show ? 'text' : 'password'" name="password" required placeholder="Min. 8 caractères"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-10 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('password') border-red-300 @enderror">
                    <button type="button" @click="show = !show" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500">
                        <i class="fa-solid text-xs" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
                @error('password') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Confirmer</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="password" name="password_confirmation" required placeholder="Répétez le mot de passe"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                </div>
            </div>

            @error('token') <p class="text-red-500 text-[9px] font-bold">{{ $message }}</p> @enderror

            <button type="submit"
                class="w-full bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                Réinitialiser le mot de passe
            </button>
        </form>
    </div>
</div>
</body>
</html>
