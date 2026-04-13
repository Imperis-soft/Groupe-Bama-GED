<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document partagé — {{ $share->document->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-50 antialiased">

<div class="min-h-full flex flex-col">

    {{-- Header --}}
    <header class="bg-white border-b border-slate-100 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-orange-600">
                    <i class="fa-solid fa-file-shield text-white text-xs"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900 tracking-tight leading-none">Groupe Bama</p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">GED — Document partagé</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 bg-green-50 border border-green-100 text-green-600 text-[9px] font-black uppercase tracking-wider px-3 py-1.5 rounded-lg">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                Lien valide
            </span>
        </div>
    </header>

    {{-- Content --}}
    <main class="flex-1 max-w-5xl mx-auto w-full px-4 py-8 space-y-5">

        {{-- Doc card --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="h-1.5 w-full bg-orange-500"></div>
            <div class="p-6 flex flex-col sm:flex-row sm:items-start gap-5">
                <div class="w-14 h-14 rounded-2xl bg-orange-50 flex items-center justify-center shrink-0 border border-orange-100">
                    <i class="fa-solid fa-file-word text-orange-500 text-2xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight leading-tight">
                        {{ $share->document->title }}
                    </h1>
                    <p class="text-xs font-mono text-slate-400 mt-1">{{ $share->document->reference }}</p>

                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider
                            {{ $share->document->status === 'approved' ? 'bg-green-50 text-green-600 border border-green-100' :
                               ($share->document->status === 'review'   ? 'bg-blue-50 text-blue-600 border border-blue-100' :
                               ($share->document->status === 'archived' ? 'bg-slate-100 text-slate-500' : 'bg-amber-50 text-amber-600 border border-amber-100')) }}">
                            {{ $share->document->status }}
                        </span>
                        @if($share->document->is_confidential)
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase bg-red-50 text-red-600 border border-red-100">
                            <i class="fa-solid fa-lock mr-1"></i>Confidentiel
                        </span>
                        @endif
                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold bg-slate-50 text-slate-500 border border-slate-100">
                            v{{ $share->document->version }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-400 mt-3">
                        Partagé par <span class="font-bold text-slate-600">{{ $share->sharedBy->full_name }}</span>
                        · {{ $share->created_at->translatedFormat('d F Y') }}
                        @if($share->expires_at)
                        · <span class="{{ $share->expires_at->isPast() ? 'text-red-500' : 'text-slate-400' }}">
                            Expire le {{ $share->expires_at->format('d/m/Y') }}
                        </span>
                        @endif
                    </p>

                    @if($share->message)
                    <div class="mt-3 bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                        <p class="text-xs text-slate-600 italic">
                            <i class="fa-solid fa-quote-left text-slate-300 mr-1.5"></i>{{ $share->message }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Niveau d'accès --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Votre niveau d'accès</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="flex items-center gap-3 p-3 rounded-xl {{ in_array($share->access_level, ['view','comment','edit']) ? 'bg-green-50 border border-green-100' : 'bg-slate-50 border border-slate-100 opacity-40' }}">
                    <i class="fa-solid fa-eye {{ in_array($share->access_level, ['view','comment','edit']) ? 'text-green-500' : 'text-slate-300' }} text-sm"></i>
                    <div>
                        <p class="text-xs font-bold {{ in_array($share->access_level, ['view','comment','edit']) ? 'text-green-700' : 'text-slate-400' }}">Lecture</p>
                        <p class="text-[9px] text-slate-400">Consulter le document</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl {{ in_array($share->access_level, ['comment','edit']) ? 'bg-purple-50 border border-purple-100' : 'bg-slate-50 border border-slate-100 opacity-40' }}">
                    <i class="fa-solid fa-comment {{ in_array($share->access_level, ['comment','edit']) ? 'text-purple-500' : 'text-slate-300' }} text-sm"></i>
                    <div>
                        <p class="text-xs font-bold {{ in_array($share->access_level, ['comment','edit']) ? 'text-purple-700' : 'text-slate-400' }}">Commentaires</p>
                        <p class="text-[9px] text-slate-400">Ajouter des notes</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 rounded-xl {{ $share->access_level === 'edit' ? 'bg-orange-50 border border-orange-100' : 'bg-slate-50 border border-slate-100 opacity-40' }}">
                    <i class="fa-solid fa-pen {{ $share->access_level === 'edit' ? 'text-orange-500' : 'text-slate-300' }} text-sm"></i>
                    <div>
                        <p class="text-xs font-bold {{ $share->access_level === 'edit' ? 'text-orange-700' : 'text-slate-400' }}">Édition</p>
                        <p class="text-[9px] text-slate-400">Modifier le document</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Actions disponibles</h2>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('documents.preview', $share->document) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all active:scale-95">
                    <i class="fa-solid fa-eye text-[10px]"></i> Visualiser
                </a>
                <a href="{{ route('documents.download', $share->document) }}"
                   class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-5 py-2.5 rounded-xl shadow-sm transition-all">
                    <i class="fa-solid fa-download text-[10px]"></i> Télécharger
                </a>
                @if(auth()->check())
                <a href="{{ route('documents.show', $share->document) }}"
                   class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all active:scale-95">
                    <i class="fa-solid fa-arrow-right text-[10px]"></i> Ouvrir dans GED
                </a>
                @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all active:scale-95">
                    <i class="fa-solid fa-sign-in-alt text-[10px]"></i> Se connecter pour plus d'accès
                </a>
                @endif
            </div>
        </div>

    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-100 bg-white py-4">
        <div class="max-w-5xl mx-auto px-4 flex items-center justify-between">
            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">© {{ date('Y') }} Groupe Bama — GED</p>
            <p class="text-[9px] text-slate-300">Solution gérée par Imperis Sarl</p>
        </div>
    </footer>

</div>

</body>
</html>
