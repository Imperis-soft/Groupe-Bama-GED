@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('users.index') }}" class="hover:text-orange-600 transition-colors">Utilisateurs</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Rôles</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Gérer les rôles</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $user->full_name }}</p>
        </div>
        <a href="{{ route('users.index') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">

            {{-- User card --}}
            <div class="flex items-center gap-3 p-4 bg-slate-50 rounded-xl mb-6">
                <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm shrink-0">
                    {{ strtoupper(substr($user->full_name, 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900">{{ $user->full_name }}</p>
                    <p class="text-[10px] text-slate-400">{{ $user->email }}</p>
                </div>
            </div>

            <form action="{{ route('users.roles.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-3 mb-6">
                    @foreach($roles as $role)
                    <label class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 hover:border-orange-200 hover:bg-orange-50/30 transition-all cursor-pointer group">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                               {{ $user->roles->contains($role) ? 'checked' : '' }}
                               class="w-4 h-4 text-orange-600 border-slate-300 rounded focus:ring-orange-500">
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-800 group-hover:text-slate-900">
                                {{ $role->display_name ?? $role->name }}
                            </p>
                            @if($role->description ?? false)
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $role->description }}</p>
                            @else
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                @if($role->name === 'admin') Accès complet à toutes les fonctionnalités
                                @elseif($role->name === 'editor') Peut créer et modifier des documents
                                @else Accès en lecture seule
                                @endif
                            </p>
                            @endif
                        </div>
                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase shrink-0
                            {{ $role->name === 'admin' ? 'bg-orange-50 text-orange-600' :
                               ($role->name === 'editor' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-500') }}">
                            {{ $role->name }}
                        </span>
                    </label>
                    @endforeach
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('users.index') }}"
                       class="flex-1 flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                        Annuler
                    </a>
                    <button type="submit"
                        class="flex-1 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-slate-50 rounded-2xl border border-slate-100 p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Rôles disponibles</h2>
            <div class="space-y-3">
                <div class="flex items-start gap-2.5">
                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase bg-orange-50 text-orange-600 shrink-0 mt-0.5">admin</span>
                    <p class="text-xs text-slate-500 leading-relaxed">Accès total — gestion des utilisateurs, suppression, configuration</p>
                </div>
                <div class="h-px bg-slate-200"></div>
                <div class="flex items-start gap-2.5">
                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase bg-blue-50 text-blue-600 shrink-0 mt-0.5">editor</span>
                    <p class="text-xs text-slate-500 leading-relaxed">Peut créer, modifier et archiver des documents</p>
                </div>
                <div class="h-px bg-slate-200"></div>
                <div class="flex items-start gap-2.5">
                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase bg-slate-100 text-slate-500 shrink-0 mt-0.5">viewer</span>
                    <p class="text-xs text-slate-500 leading-relaxed">Lecture seule — consultation et téléchargement uniquement</p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
