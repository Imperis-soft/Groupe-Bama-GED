@extends('layouts.app')

@section('content')
<div class="space-y-6">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">
                Bonjour, {{ explode(' ', auth()->user()->full_name)[0] }} 👋
            </h1>
            <p class="text-xs text-slate-400 font-medium mt-1">
                {{ now()->translatedFormat('l d F Y') }} — Tableau de bord GED
            </p>
        </div>
        <a href="{{ route('documents.index') }}"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-3 rounded-xl shadow-lg shadow-orange-200 transition-all self-start sm:self-auto">
            <i class="fa-solid fa-plus text-[10px]"></i> Nouveau document
        </a>
    </div>

    {{-- ===== KPI CARDS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">

        {{-- Documents --}}
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                        {{ $isAdmin ? 'Documents' : 'Mes documents' }}
                    </p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($documentsCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-lines text-blue-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-[9px] font-bold text-green-600 uppercase tracking-wider">Stockage actif</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-blue-50/60 group-hover:bg-blue-100/60 transition-colors"></div>
        </div>

        {{-- Catégories (admin) / Partagés avec moi (non-admin) --}}
        @if($isAdmin)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Catégories</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($categoriesCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-folder-tree text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Structure indexée</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-purple-50/60 group-hover:bg-purple-100/60 transition-colors"></div>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Partagés</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($sharedWithMeCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-share-nodes text-purple-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Avec moi</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-purple-50/60 group-hover:bg-purple-100/60 transition-colors"></div>
        </div>
        @endif

        {{-- Utilisateurs (admin) / Approbations en attente (non-admin) --}}
        @if($isAdmin)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Utilisateurs</p>
                    <p class="text-3xl font-black text-slate-900 mt-1 leading-none">{{ number_format($usersCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-users text-orange-500 text-sm"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1.5">
                <i class="fa-solid fa-shield-check text-orange-500 text-[9px]"></i>
                <span class="text-[9px] font-bold text-orange-600 uppercase tracking-wider">Accès sécurisés</span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full bg-orange-50/60 group-hover:bg-orange-100/60 transition-colors"></div>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">À approuver</p>
                    <p class="text-3xl font-black {{ $pendingApprovalsCount > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-1 leading-none">{{ number_format($pendingApprovalsCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl {{ $pendingApprovalsCount > 0 ? 'bg-amber-50' : 'bg-slate-50' }} flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-list-check {{ $pendingApprovalsCount > 0 ? 'text-amber-500' : 'text-slate-400' }} text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold {{ $pendingApprovalsCount > 0 ? 'text-amber-500' : 'text-slate-400' }} uppercase tracking-wider">
                    {{ $pendingApprovalsCount > 0 ? 'Action requise' : 'Aucune en attente' }}
                </span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full {{ $pendingApprovalsCount > 0 ? 'bg-amber-50/60' : 'bg-slate-50/60' }} group-hover:opacity-80 transition-colors"></div>
        </div>
        @endif

        {{-- Expirés --}}
        <div class="bg-white rounded-2xl border border-slate-100 p-4 md:p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Expirés</p>
                    <p class="text-3xl font-black {{ $expiredCount > 0 ? 'text-red-600' : 'text-slate-900' }} mt-1 leading-none">{{ number_format($expiredCount) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl {{ $expiredCount > 0 ? 'bg-red-50' : 'bg-slate-50' }} flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-triangle-exclamation {{ $expiredCount > 0 ? 'text-red-500' : 'text-slate-400' }} text-sm"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-[9px] font-bold {{ $expiredCount > 0 ? 'text-red-500' : 'text-slate-400' }} uppercase tracking-wider">
                    {{ $expiredCount > 0 ? 'Action requise' : 'Aucun expiré' }}
                </span>
            </div>
            <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full {{ $expiredCount > 0 ? 'bg-red-50/60' : 'bg-slate-50/60' }} group-hover:opacity-80 transition-colors"></div>
        </div>

    </div>

    {{-- ===== MAIN GRID ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-6">

        {{-- Documents récents (2/3) --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Activité récente</h2>
                </div>
                <a href="{{ route('documents.index') }}"
                   class="text-[10px] font-bold text-slate-400 hover:text-orange-600 transition-colors flex items-center gap-1">
                    Tout voir <i class="fa-solid fa-arrow-right text-[8px]"></i>
                </a>
            </div>

            <div class="divide-y divide-slate-50">
                @forelse($recentDocuments as $doc)
                <a href="{{ route('documents.show', $doc) }}"
                   class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/70 transition-colors group">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 group-hover:bg-orange-600 group-hover:text-white transition-all">
                        <i class="fa-solid fa-file-word text-slate-400 text-sm group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-800 truncate leading-tight">{{ $doc->title }}</p>
                        <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                            {{ $doc->reference }}
                            <span class="mx-1 text-slate-200">•</span>
                            {{ $doc->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="hidden sm:inline-block px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase
                            {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                               ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' :
                                                              'bg-slate-100 text-slate-500') }}">
                            {{ statusLabel($doc->status) }}
                        </span>
                        <span class="hidden md:inline-block px-2 py-0.5 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-bold">
                            {{ $doc->category?->name ?? 'Général' }}
                        </span>
                    </div>
                </a>
                @empty
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-inbox text-slate-300 text-xl"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-400">Aucun document pour l'instant</p>
                    <a href="{{ route('documents.index') }}" class="mt-3 text-[10px] font-black text-orange-600 hover:underline uppercase tracking-wider">
                        Créer le premier
                    </a>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Colonne droite (1/3) --}}
        <div class="flex flex-col gap-4">

            {{-- Statut infrastructure --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-900 uppercase tracking-widest mb-4">Infrastructure</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $dbStatus === 'connected' ? 'bg-green-500' : 'bg-red-500' }} animate-pulse"></div>
                            <span class="text-xs font-semibold text-slate-600">Base de données</span>
                        </div>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-lg
                            {{ $dbStatus === 'connected' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }}">
                            {{ $dbStatus === 'connected' ? 'Connecté' : 'Erreur' }}
                        </span>
                    </div>
                    <div class="h-px bg-slate-50"></div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ str_starts_with($storageStatus, 'ok') ? 'bg-green-500' : 'bg-red-500' }} animate-pulse"></div>
                            <span class="text-xs font-semibold text-slate-600">MinIO Storage</span>
                        </div>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-lg
                            {{ str_starts_with($storageStatus, 'ok') ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }}">
                            {{ str_starts_with($storageStatus, 'ok') ? 'Opérationnel' : 'Erreur' }}
                        </span>
                    </div>
                    <div class="h-px bg-slate-50"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-600">Espace libre</span>
                        <span class="text-xs font-black text-slate-800">
                            @if($diskFree)
                                {{ number_format($diskFree / 1024 / 1024 / 1024, 1) }} GB
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Conformité --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-900 uppercase tracking-widest mb-4">Conformité</h2>
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-slate-900">{{ number_format($archivedCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Archivés</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black {{ $expiredCount > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ number_format($expiredCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Expirés</p>
                    </div>
                    @if($isAdmin)
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-purple-600">{{ number_format($confidentialCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Confidentiels</p>
                    </div>
                    @else
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-amber-600">{{ number_format($draftCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Brouillons</p>
                    </div>
                    @endif
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-blue-600">{{ number_format($reviewCount) }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">En révision</p>
                    </div>
                </div>
            </div>

            {{-- CTA --}}
            <div class="bg-slate-900 rounded-2xl p-5 text-white shadow-xl shadow-slate-200 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-orange-600/20 rounded-full -translate-y-8 translate-x-8"></div>
                <div class="absolute bottom-0 left-0 w-16 h-16 bg-orange-600/10 rounded-full translate-y-6 -translate-x-4"></div>
                <div class="relative">
                    <p class="text-[8px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Action rapide</p>
                    <h3 class="text-sm font-black mb-1 leading-snug">Archiver un nouveau document</h3>
                    <p class="text-[10px] text-slate-400 mb-4">Générez et stockez en quelques secondes.</p>
                    <a href="{{ route('documents.index') }}"
                       class="flex items-center justify-center gap-2 w-full bg-orange-600 hover:bg-orange-500 text-white py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95">
                        <i class="fa-solid fa-plus text-[9px]"></i> Nouveau document
                    </a>
                </div>
            </div>

        </div>
    </div>

    {{-- ===== FAVORIS ===== --}}
    @php $favs = auth()->user()->favorites()->with('category')->latest('document_favorites.created_at')->limit(5)->get(); @endphp
    @if($favs->count())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-star text-amber-400 text-sm"></i>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Mes favoris</h2>
            </div>
            <a href="{{ route('documents.favorites') }}" class="text-[10px] font-bold text-slate-400 hover:text-orange-600 transition-colors">
                Voir tous <i class="fa-solid fa-arrow-right text-[8px]"></i>
            </a>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($favs as $doc)
            <a href="{{ route('documents.show', $doc) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50/60 transition-colors group">
                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                    <i class="fa-solid fa-file-word text-amber-500 text-xs group-hover:text-white transition-colors"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ $doc->title }}</p>
                    <p class="text-[9px] text-slate-400 font-mono">{{ $doc->reference }}</p>
                </div>
                <span class="text-[9px] font-bold text-slate-400 shrink-0">{{ $doc->category?->name ?? 'Général' }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ===== AUDIT LOG ===== --}}
    @if($recentActivities->count())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Journal d'audit</h2>
            </div>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($recentActivities->take(5) as $activity)
            <div class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50/50 transition-colors">
                <div class="w-7 h-7 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-bolt text-orange-500 text-[9px]"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-800 truncate">
                        <span class="text-orange-600">{{ actionLabel($activity->action) }}</span>
                        <span class="text-slate-400 font-normal"> — {{ Str::limit($activity->document->title ?? '—', 40) }}</span>
                    </p>
                    <p class="text-[9px] text-slate-400 mt-0.5">
                        {{ $activity->user->full_name ?? 'Système' }}
                    </p>
                </div>
                <span class="text-[9px] text-slate-400 font-mono shrink-0">{{ $activity->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
