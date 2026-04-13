<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé — Groupe Bama GED</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="h-full bg-slate-50 flex items-center justify-center p-4">
<div class="text-center max-w-md">
    <div class="w-20 h-20 rounded-3xl bg-red-100 flex items-center justify-center mx-auto mb-6">
        <i class="fa-solid fa-shield-halved text-red-500 text-4xl"></i>
    </div>
    <h1 class="text-6xl font-black text-slate-900 mb-2">403</h1>
    <h2 class="text-xl font-black text-slate-700 mb-3">Accès refusé</h2>
    <p class="text-sm text-slate-400 mb-8 leading-relaxed">
        Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.<br>
        Contactez un administrateur si vous pensez que c'est une erreur.
    </p>
    <div class="flex items-center justify-center gap-3">
        <a href="{{ url()->previous() }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-5 py-3 rounded-xl shadow-sm transition-all">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-black uppercase tracking-widest px-5 py-3 rounded-xl shadow-lg shadow-orange-200 transition-all">
            <i class="fa-solid fa-house text-[10px]"></i> Tableau de bord
        </a>
    </div>
    <p class="text-[9px] text-slate-300 mt-8 uppercase tracking-widest">Groupe Bama GED — © {{ date('Y') }}</p>
</div>
</body>
</html>
