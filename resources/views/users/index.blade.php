@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Utilisateurs</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">{{ $users->total() }} membre(s) enregistré(s)</p>
        </div>
        @if(auth()->user()->hasRole('admin'))
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all self-start sm:self-auto">
            <i class="fa-solid fa-user-plus text-[10px]"></i> Inviter un membre
        </a>
        @endif
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- Desktop --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Membre</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Rôles</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Téléphone</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Inscrit le</th>
                        @if(auth()->user()->hasRole('admin'))
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50/60 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="relative shrink-0">
                                    <div class="w-9 h-9 rounded-xl bg-slate-900 group-hover:bg-orange-600 flex items-center justify-center text-white text-[10px] font-black transition-colors">
                                        {{ strtoupper(substr($user->full_name, 0, 2)) }}
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900 leading-tight">{{ $user->full_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->roles as $role)
                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider
                                    {{ $role->name === 'admin' ? 'bg-orange-50 text-orange-600 border border-orange-100' :
                                       ($role->name === 'editor' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $role->display_name ?? $role->name }}
                                </span>
                                @empty
                                <span class="text-[9px] text-slate-300 font-bold">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs text-slate-500 font-medium">{{ $user->phone ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-slate-700">{{ $user->created_at->format('d/m/Y') }}</p>
                            <p class="text-[9px] text-slate-400">{{ $user->created_at->diffForHumans() }}</p>
                        </td>
                        @if(auth()->user()->hasRole('admin'))
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('users.edit', $user) }}"
                                   title="Modifier"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </a>
                                <a href="{{ route('users.roles.edit', $user) }}"
                                   title="Gérer les rôles"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-orange-50 hover:text-orange-600 transition-all">
                                    <i class="fa-solid fa-shield-halved text-xs"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                      onsubmit="return confirm('Supprimer {{ $user->full_name }} ?');" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden divide-y divide-slate-50">
            @foreach($users as $user)
            <div class="px-4 py-4">
                <div class="flex items-start gap-3">
                    <div class="relative shrink-0">
                        <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white text-[10px] font-black">
                            {{ strtoupper(substr($user->full_name, 0, 2)) }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-900 truncate">{{ $user->full_name }}</p>
                        <p class="text-[10px] text-slate-400 truncate">{{ $user->email }}</p>
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            @forelse($user->roles as $role)
                            <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase
                                {{ $role->name === 'admin' ? 'bg-orange-50 text-orange-600' : 'bg-slate-100 text-slate-500' }}">
                                {{ $role->display_name ?? $role->name }}
                            </span>
                            @empty
                            <span class="text-[9px] text-slate-300">Aucun rôle</span>
                            @endforelse
                        </div>
                    </div>
                    @if(auth()->user()->hasRole('admin'))
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('users.edit', $user) }}"
                           class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </a>
                        <a href="{{ route('users.roles.edit', $user) }}"
                           class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-orange-50 hover:text-orange-600 transition-all">
                            <i class="fa-solid fa-shield-halved text-xs"></i>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                {{ $users->firstItem() }}–{{ $users->lastItem() }} sur {{ $users->total() }} membres
            </p>
            {{ $users->links() }}
        </div>
        @endif

    </div>
</div>
@endsection
