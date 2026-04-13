@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('users.index') }}" class="hover:text-orange-600 transition-colors">Utilisateurs</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Nouveau membre</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Inviter un membre</h1>
        </div>
        <a href="{{ route('users.index') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-5 md:p-6">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-user-plus text-orange-500 text-xs"></i>
                </div>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Informations du membre</h2>
            </div>

            <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Nom complet <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" required
                               placeholder="Prénom Nom"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('full_name') border-red-300 @enderror">
                        @error('full_name') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="email@exemple.com"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('email') border-red-300 @enderror">
                        @error('email') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Mot de passe <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required
                               placeholder="Min. 8 caractères"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('password') border-red-300 @enderror">
                        @error('password') <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Confirmer le mot de passe <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" required
                               placeholder="Répétez le mot de passe"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               placeholder="+223 XX XX XX XX"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Adresse</label>
                        <input type="text" name="address" value="{{ old('address') }}"
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
                        <i class="fa-solid fa-user-plus mr-1.5"></i> Créer le compte
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-slate-50 rounded-2xl border border-slate-100 p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">À savoir</h2>
            <div class="space-y-3">
                <div class="flex items-start gap-2.5">
                    <div class="w-5 h-5 rounded-lg bg-orange-100 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-key text-orange-500 text-[8px]"></i>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Le mot de passe doit contenir au moins 8 caractères.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <div class="w-5 h-5 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-shield-halved text-blue-500 text-[8px]"></i>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Les rôles peuvent être assignés après la création depuis la liste des utilisateurs.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <div class="w-5 h-5 rounded-lg bg-green-100 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-envelope text-green-500 text-[8px]"></i>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">L'email doit être unique dans le système.</p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
