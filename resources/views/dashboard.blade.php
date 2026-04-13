@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Tableau de bord</h1>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.15em] mt-0.5">
                Session active : <span class="text-orange-600">{{ auth()->user()->full_name }}</span>
            </p>
        </div>
        <div class="hidden md:block">
            <span class="bg-white px-3 py-1.5 rounded-lg shadow-sm border border-slate-100 text-[9px] font-black text-slate-400 uppercase tracking-widest">
                <i class="fa-regular fa-calendar-check mr-1.5 text-orange-500"></i> {{ now()->translatedFormat('l d F Y') }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="relative z-10">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Documents</p>
                <div class="text-2xl font-black text-slate-900 leading-none">{{ number_format($documentsCount) }}</div>
                <div class="mt-3 flex items-center text-green-600 text-[9px] font-black uppercase bg-green-50 w-fit px-2 py-0.5 rounded">
                    <span class="w-1 h-1 bg-green-500 rounded-full mr-1.5 animate-pulse"></span> Stockage actif
                </div>
            </div>
            <i class="fa-solid fa-file-invoice absolute -right-1 -bottom-1 text-5xl text-slate-50 group-hover:text-orange-50/50 transition-colors"></i>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="relative z-10">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Catégories</p>
                <div class="text-2xl font-black text-slate-900 leading-none">{{ number_format($categoriesCount) }}</div>
                <p class="mt-3 text-[9px] text-slate-400 font-bold uppercase tracking-tighter">Structure indexée</p>
            </div>
            <i class="fa-solid fa-layer-group absolute -right-1 -bottom-1 text-5xl text-slate-50 group-hover:text-orange-50/50 transition-colors"></i>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group">
            <div class="relative z-10">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Utilisateurs</p>
                <div class="text-2xl font-black text-slate-900 leading-none">{{ number_format($usersCount) }}</div>
                <div class="mt-3 flex items-center text-orange-600 text-[9px] font-black uppercase bg-orange-50 w-fit px-2 py-0.5 rounded">
                    <i class="fa-solid fa-shield-check mr-1.5"></i> Accès sécurisés
                </div>
            </div>
            <i class="fa-solid fa-users absolute -right-1 -bottom-1 text-5xl text-slate-50 group-hover:text-orange-50/50 transition-colors"></i>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-orange-500 rounded-full"></span> Activité Récente
                </h2>
                <a href="{{ route('documents.index') }}" class="text-[9px] font-black text-slate-400 hover:text-orange-600 uppercase tracking-tighter transition-colors">Tout explorer</a>
            </div>
            
            <div class="space-y-2">
                @forelse($recentDocuments as $doc)
                    <div class="flex items-center justify-between p-2.5 rounded-xl border border-transparent hover:border-slate-100 hover:bg-slate-50/50 transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400 group-hover:bg-slate-900 group-hover:text-white transition-all">
                                <i class="fa-solid fa-file-word text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 text-[11px] leading-tight">{{ Str::limit($doc->title, 45) }}</h3>
                                <p class="text-[9px] text-slate-400 mt-0.5 font-mono uppercase">
                                    {{ $doc->reference }} <span class="mx-1 opacity-30">•</span> {{ $doc->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[8px] font-black uppercase tracking-tighter">
                            {{ $doc->category?->name ?? 'Général' }}
                        </span>
                    </div>
                @empty
                    <div class="text-center py-6">
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest italic opacity-50 text-center">Néant.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                <h2 class="text-[10px] font-black text-slate-900 mb-4 uppercase tracking-[0.2em]">Infrastructure</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-500 font-bold uppercase tracking-tighter">Base SQL</span>
                        <span class="font-black {{ $dbStatus == 'Connecté' ? 'text-green-600' : 'text-red-600' }} uppercase">
                            {{ $dbStatus }}
                        </span>
                    </div>
                    <div class="h-px bg-slate-50"></div>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-500 font-bold uppercase tracking-tighter">Storage Engine</span>
                        <span class="font-black {{ $storageStatus == 'Opérationnel' ? 'text-green-600' : 'text-red-600' }} uppercase">
                            {{ $storageStatus }}
                        </span>
                    </div>
                    <div class="h-px bg-slate-50"></div>
                    <div class="flex items-center justify-between text-[10px]">
                        <span class="text-slate-500 font-bold uppercase tracking-tighter">Quota Libre</span>
                        <span class="text-slate-900 font-black">
                            @if($diskFree) {{ round($diskFree / 1024 / 1024, 1) }} MB @else N/A @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 rounded-2xl p-5 text-white shadow-xl shadow-slate-200">
                <p class="text-[8px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Quick Action</p>
                <h3 class="text-sm font-bold mb-4 leading-tight">Ajouter une pièce à l'archive ?</h3>
                <a href="{{ route('documents.index') }}" class="w-full bg-orange-600 text-white py-2.5 rounded-lg font-black text-[9px] flex items-center justify-center gap-2 hover:bg-orange-500 transition-all uppercase tracking-widest">
                    <i class="fa-solid fa-plus text-[8px]"></i> Nouveau Document
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-[10px] font-black text-slate-900 tracking-widest uppercase mb-5">Statistiques de Conformité</h2>
            <div class="grid grid-cols-4 gap-2">
                <div class="text-center p-2 bg-slate-50 rounded-xl">
                    <div class="text-lg font-black text-slate-900">{{ number_format($archivedCount) }}</div>
                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-tighter">Archivés</p>
                </div>
                <div class="text-center p-2 bg-slate-50 rounded-xl">
                    <div class="text-lg font-black text-red-600">{{ number_format($expiredCount) }}</div>
                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-tighter">Expirés</p>
                </div>
                <div class="text-center p-2 bg-slate-50 rounded-xl">
                    <div class="text-lg font-black text-purple-600">{{ number_format($confidentialCount) }}</div>
                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-tighter">Secrets</p>
                </div>
                <div class="text-center p-2 bg-slate-50 rounded-xl">
                    <div class="text-lg font-black text-blue-600">{{ number_format($reviewCount) }}</div>
                    <p class="text-[7px] font-black text-slate-400 uppercase tracking-tighter">Révisions</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
            <h2 class="text-[10px] font-black text-slate-900 tracking-widest uppercase mb-4">Audit de sécurité</h2>
            <div class="space-y-2">
                @foreach($recentActivities->take(3) as $activity)
                    <div class="flex items-center justify-between text-[9px] py-1 border-b border-slate-50 last:border-0">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-orange-500 text-[8px]"></i>
                            <span class="text-slate-900 font-bold italic">{{ $activity->action }}</span>
                            <span class="text-slate-400">— {{ Str::limit($activity->document->title, 20) }}</span>
                        </div>
                        <span class="text-slate-400 font-mono">{{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection