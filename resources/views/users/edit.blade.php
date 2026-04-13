@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('users.index') }}" class="hover:text-orange-600 transition-colors">Utilisateurs</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Modifier</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Modifier le membre</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $user->email }}</p>
        </div>
        <a href="{{ route('users.index') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm shrink-0">
                    {{ strtoupper(substr($user->full_name, 0, 2)) }}
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900">{{ $user->full_name }}</p>
                    <p class="text-[10px] text-slate-400">Membre depuis {{ $user->created_at->format('d/m/Y') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nom complet <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" required
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('full_name') border-red-300 @enderror">
                        @error('full_name') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('email') border-red-300 @enderror">
                        @error('email') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                               placeholder="+223 XX XX XX XX"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Adresse</label>
                        <input type="text" name="address" value="{{ old('address', $user->address) }}"
                               placeholder="Bamako, Mali"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
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

        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Rôles actuels</h2>
                <div class="flex flex-wrap gap-1.5 mb-4">
                    @forelse($user->roles as $role)
                    <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider
                        {{ $role->name === 'admin' ? 'bg-orange-50 text-orange-600 border border-orange-100' :
                           ($role->name === 'editor' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-slate-100 text-slate-500') }}">
                        {{ $role->display_name ?? $role->name }}
                    </span>
                    @empty
                    <p class="text-xs text-slate-300">Aucun rôle assigné</p>
                    @endforelse
                </div>
                <a href="{{ route('users.roles.edit', $user) }}"
                   class="flex items-center justify-center gap-2 w-full bg-slate-100 hover:bg-orange-50 hover:text-orange-600 text-slate-600 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                    <i class="fa-solid fa-shield-halved text-[10px]"></i> Gérer les rôles
                </a>
            </div>

            <div class="bg-slate-50 rounded-2xl border border-slate-100 p-4 space-y-2">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Informations</p>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-400">ID</span>
                    <span class="font-mono font-bold text-slate-600">#{{ $user->id }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-400">Inscrit le</span>
                    <span class="font-bold text-slate-600">{{ $user->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-slate-400">Modifié le</span>
                    <span class="font-bold text-slate-600">{{ $user->updated_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
