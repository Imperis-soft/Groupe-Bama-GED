<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-4">
<div class="w-full max-w-sm">
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-orange-600 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-orange-200">
            <i class="fa-solid fa-key text-white text-xl"></i>
        </div>
        <h1 class="text-2xl font-black text-slate-900">Mot de passe oublié</h1>
        <p class="text-sm text-slate-400 mt-2">Entrez votre email pour recevoir un lien de réinitialisation.</p>
    </div>

    @if(session('success'))
    <div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-100 rounded-2xl px-4 py-3">
        <i class="fa-solid fa-check-circle text-green-500 shrink-0"></i>
        <p class="text-xs font-bold text-green-700">{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <form action="{{ route('password.send') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Email</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="votre@email.com"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-9 pr-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('email') border-red-300 @enderror">
                </div>
                @error('email') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                class="w-full bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                Envoyer le lien
            </button>
        </form>
    </div>

    <div class="text-center mt-5">
        <a href="{{ route('login') }}" class="text-xs font-bold text-slate-400 hover:text-orange-600 transition-colors">
            <i class="fa-solid fa-arrow-left mr-1 text-[9px]"></i> Retour à la connexion
        </a>
    </div>
</div>
</body>
</html>
