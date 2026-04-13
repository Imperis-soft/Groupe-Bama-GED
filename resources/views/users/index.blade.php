@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="bg-orange-600 w-2 h-6 rounded-full"></span>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Gestion d'Équipe</h1>
            </div>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">Groupe Bama — Annuaire Collaborateurs</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div class="bg-white px-5 py-3 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
                <div class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center border border-slate-100">
                    <i class="fa-solid fa-user-shield text-sm"></i>
                </div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-tighter leading-none">Total Actifs</p>
                    <p class="text-lg font-black text-slate-900 leading-none mt-1">{{ $users->total() }}</p>
                </div>
            </div>

            @if(auth()->check() && in_array(auth()->user()->email, ['admin@bama.com','contact@imperis.com']))
                <a href="{{ route('users.create') }}" class="bg-slate-900 hover:bg-orange-600 text-white px-6 py-3.5 rounded-2xl text-xs font-black transition-all flex items-center gap-3 shadow-xl shadow-slate-200 uppercase tracking-widest active:scale-95">
                    <i class="fa-solid fa-user-plus text-sm"></i>
                    Inviter un membre
                </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        
        <div class="p-6 border-b border-slate-50 bg-slate-50/30 flex flex-col md:flex-row gap-4 justify-between items-center">
            <div class="relative w-full md:w-96">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" placeholder="Filtrer par nom ou email..." class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-medium focus:ring-2 focus:ring-orange-500 outline-none transition-all">
            </div>
            <div class="flex gap-2">
                <button class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-orange-600 transition-colors"><i class="fa-solid fa-filter text-xs"></i></button>
                <button class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-orange-600 transition-colors"><i class="fa-solid fa-download text-xs"></i></button>
            </div>
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Collaborateur</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Accès & Rôles</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Arrivée</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($users as $user)
                    <tr class="hover:bg-orange-50/10 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <div class="w-11 h-11 bg-slate-900 rounded-2xl flex items-center justify-center text-white text-[10px] font-black shadow-lg shadow-slate-200 group-hover:bg-orange-600 transition-colors">
                                        {{ strtoupper(substr($user->full_name, 0, 2)) }}
                                    </div>
                                    <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-slate-900 leading-none mb-1">{{ $user->full_name }}</p>
                                    <p class="text-[11px] text-slate-400 font-medium">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-slate-200">Collaborateur</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="text-[11px] font-bold text-slate-700">{{ $user->created_at->translatedFormat('d M Y') }}</div>
                            <div class="text-[9px] text-slate-400 font-medium tracking-widest uppercase">à {{ $user->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex justify-end items-center gap-1">
                                <a href="{{ route('users.edit', $user) }}" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Modifier">
                                    <i class="fa-solid fa-pen-to-square text-sm"></i>
                                </a>
                                <a href="{{ route('users.roles.edit', $user) }}" class="p-2 text-slate-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-all" title="Rôles">
                                    <i class="fa-solid fa-shield-halved text-sm"></i>
                                </a>
                                @if(auth()->check() && in_array(auth()->user()->email, ['admin@bama.com','contact@imperis.com']))
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer ?');" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="md:hidden grid grid-cols-1 divide-y divide-slate-50">
            @foreach($users as $user)
            <div class="p-6 flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center text-white text-[10px] font-black uppercase">
                            {{ strtoupper(substr($user->full_name, 0, 2)) }}
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900">{{ $user->full_name }}</p>
                            <p class="text-[10px] text-slate-400 font-medium">{{ $user->email }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 bg-green-50 text-green-600 rounded text-[8px] font-black uppercase tracking-tighter">Actif</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $user->created_at->format('d/m/Y') }}</span>
                    <div class="flex gap-3">
                        <a href="{{ route('users.edit', $user) }}" class="text-blue-600 text-xs font-black uppercase tracking-widest">Éditer</a>
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-red-500 text-xs font-black uppercase tracking-widest">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 px-4">
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Affichage de {{ $users->firstItem() ?? 0 }} à {{ $users->lastItem() ?? 0 }} sur {{ $users->total() }} membres</p>
        <div class="pagination-custom">
            {{ $users->links() }}
        </div>
    </div>
</div>

<style>
    /* Nettoyage de la pagination par défaut pour la rendre "SaaS" */
    .pagination-custom nav { @apply shadow-none border-none; }
    .pagination-custom span, .pagination-custom a { @apply rounded-xl border-none mx-0.5 text-xs font-black transition-all !important; }
    .pagination-custom .relative.inline-flex { @apply shadow-none border-none; }
</style>
@endsection