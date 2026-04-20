@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Mon profil</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Gérez vos informations personnelles et votre sécurité</p>
        </div>
        {{-- Avatar --}}
        <div class="flex items-center gap-3 bg-white border border-slate-100 rounded-2xl px-4 py-3 shadow-sm self-start sm:self-auto">
            <div class="w-9 h-9 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm shrink-0">
                {{ strtoupper(substr($user->full_name, 0, 2)) }}
            </div>
            <div>
                <p class="text-xs font-black text-slate-900 leading-tight">{{ $user->full_name }}</p>
                <div class="flex flex-wrap gap-1 mt-0.5">
                    @forelse($user->roles as $role)
                    <span class="text-[8px] font-black uppercase px-1.5 py-0.5 rounded
                        {{ $role->name === 'admin' ? 'bg-orange-50 text-orange-600' :
                           ($role->name === 'editor' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-500') }}">
                        {{ $role->display_name ?? $role->name }}
                    </span>
                    @empty
                    <span class="text-[8px] text-slate-300 font-bold">Aucun rôle</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- Colonne principale --}}
        <div class="xl:col-span-2 space-y-4">

            {{-- Informations personnelles --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                    <div class="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-user text-orange-500 text-xs"></i>
                    </div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Informations personnelles</h2>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" class="p-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Nom complet <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}" required
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all @error('full_name') border-red-300 @enderror">
                        @error('full_name')
                            <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Email</label>
                        <div class="relative">
                            <input type="email" value="{{ $user->email }}" disabled
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-400 cursor-not-allowed pr-10">
                            <i class="fa-solid fa-lock absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                        </div>
                        <p class="text-[9px] text-slate-400 mt-1">L'adresse email ne peut pas être modifiée.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
                            <i class="fa-solid fa-floppy-disk text-[10px]"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>

            {{-- Mot de passe --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-lock text-slate-500 text-xs"></i>
                    </div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Changer le mot de passe</h2>
                </div>

                <form action="{{ route('profile.password') }}" method="POST" class="p-5 space-y-4"
                      x-data="{ strength: 0, strengthLabel: '', strengthColor: '',
                        checkStrength(v) {
                            let s = 0;
                            if (v.length >= 8)  s++;
                            if (/[A-Z]/.test(v)) s++;
                            if (/[0-9]/.test(v)) s++;
                            if (/[^A-Za-z0-9]/.test(v)) s++;
                            this.strength = s;
                            const labels = ['', 'Faible', 'Moyen', 'Bon', 'Fort'];
                            const colors = ['', 'bg-red-500', 'bg-amber-500', 'bg-blue-500', 'bg-green-500'];
                            this.strengthLabel = labels[s] || '';
                            this.strengthColor = colors[s] || '';
                        }
                      }">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                            Mot de passe actuel <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="current_password" required
                               placeholder="••••••••"
                               class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('current_password') border-red-300 @enderror">
                        @error('current_password')
                            <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                Nouveau mot de passe <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password" required
                                   placeholder="Min. 8 caractères"
                                   @input="checkStrength($event.target.value)"
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all @error('password') border-red-300 @enderror">
                            {{-- Barre de force --}}
                            <div x-show="strength > 0" class="mt-2 space-y-1">
                                <div class="flex gap-1">
                                    <template x-for="i in 4" :key="i">
                                        <div class="h-1.5 flex-1 rounded-full transition-all"
                                             :class="i <= strength ? strengthColor : 'bg-slate-100'"></div>
                                    </template>
                                </div>
                                <p class="text-[9px] font-bold" :class="{
                                    'text-red-500': strength === 1,
                                    'text-amber-500': strength === 2,
                                    'text-blue-500': strength === 3,
                                    'text-green-500': strength === 4
                                }" x-text="strengthLabel"></p>
                            </div>
                            @error('password')
                                <p class="text-red-500 text-[9px] font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">
                                Confirmer <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password_confirmation" required
                                   placeholder="Répétez le mot de passe"
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-700 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl transition-all">
                            <i class="fa-solid fa-key text-[10px]"></i> Changer le mot de passe
                        </button>
                    </div>
                </form>
            </div>

        </div>

        {{-- Sidebar droite --}}
        <div class="space-y-4">

            {{-- Infos compte --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Mon compte</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">ID</span>
                        <span class="font-mono font-bold text-slate-600">#Bama-P{{ $user->id }}</span>
                    </div>
                    <div class="h-px bg-slate-50"></div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">Email</span>
                        <span class="font-bold text-slate-600 truncate max-w-[140px]">{{ $user->email }}</span>
                    </div>
                    <div class="h-px bg-slate-50"></div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">Inscrit le</span>
                        <span class="font-bold text-slate-600">{{ $user->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="h-px bg-slate-50"></div>
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-slate-400 font-medium">Dernière modif.</span>
                        <span class="font-bold text-slate-600">{{ $user->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            {{-- Rôles --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Mes rôles</h2>
                <div class="flex flex-wrap gap-2">
                    @forelse($user->roles as $role)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider
                        {{ $role->name === 'admin'  ? 'bg-orange-50 text-orange-600 border border-orange-100' :
                           ($role->name === 'editor' ? 'bg-blue-50 text-blue-600 border border-blue-100' :
                                                       'bg-slate-100 text-slate-500 border border-slate-200') }}">
                        <i class="fa-solid fa-shield-halved text-[9px]"></i>
                        {{ $role->display_name ?? $role->name }}
                    </span>
                    @empty
                    <p class="text-xs text-slate-300 font-bold">Aucun rôle assigné</p>
                    @endforelse
                </div>
            </div>

            {{-- Sécurité tips --}}
            <div class="bg-slate-50 rounded-2xl border border-slate-100 p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Conseils sécurité</h2>
                <div class="space-y-3">
                    <div class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-lg bg-green-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-check text-green-500 text-[8px]"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 leading-relaxed">Utilisez un mot de passe d'au moins 8 caractères avec des chiffres et symboles.</p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-[8px]"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 leading-relaxed">Ne partagez jamais vos identifiants avec d'autres personnes.</p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <div class="w-5 h-5 rounded-lg bg-blue-100 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-rotate text-blue-500 text-[8px]"></i>
                        </div>
                        <p class="text-[10px] text-slate-500 leading-relaxed">Changez votre mot de passe régulièrement.</p>
                    </div>
                </div>
            </div>

            {{-- Historique de connexions --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Dernières connexions</h2>
                @php $logins = $user->loginHistories()->limit(5)->get(); @endphp
                @if($logins->isEmpty())
                <p class="text-xs text-slate-300 font-bold">Aucun historique</p>
                @else
                <div class="space-y-2">
                    @foreach($logins as $login)
                    <div class="flex items-center gap-3 p-2.5 rounded-xl {{ $login->success ? 'bg-green-50' : 'bg-red-50' }}">
                        <div class="w-6 h-6 rounded-lg {{ $login->success ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center shrink-0">
                            <i class="fa-solid {{ $login->success ? 'fa-check text-green-500' : 'fa-xmark text-red-500' }} text-[9px]"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] font-bold {{ $login->success ? 'text-green-700' : 'text-red-700' }}">
                                {{ $login->success ? 'Connexion réussie' : 'Tentative échouée' }}
                            </p>
                            <p class="text-[9px] text-slate-400 font-mono truncate">{{ $login->ip_address }} · {{ $login->logged_at->diffForHumans() }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Statistiques personnelles --}}
            @php
                $myDocsCount     = \App\Models\Document::where('creator_id', $user->id)->count();
                $myActionsCount  = \App\Models\DocumentAuditLog::where('user_id', $user->id)->count();
                $myApprovCount   = \App\Models\ApprovalStep::where('approver_id', $user->id)->where('status', 'approved')->count();
                $myCommentsCount = \App\Models\DocumentComment::where('user_id', $user->id)->count();
            @endphp
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Mes statistiques</h2>
                    <a href="{{ route('profile.activity') }}"
                       class="text-[9px] font-black text-orange-600 hover:underline uppercase tracking-wider">
                        Voir tout <i class="fa-solid fa-arrow-right text-[8px]"></i>
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-slate-900">{{ $myDocsCount }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Documents créés</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-blue-600">{{ $myActionsCount }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Actions totales</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-green-600">{{ $myApprovCount }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Approbations</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <p class="text-xl font-black text-purple-600">{{ $myCommentsCount }}</p>
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-wider mt-0.5">Commentaires</p>
                    </div>
                </div>
            </div>

            {{-- Liens rapides --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Accès rapide</h2>
                <div class="space-y-2">
                    <a href="{{ route('profile.activity') }}"
                       class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-orange-50 hover:text-orange-700 text-slate-600 text-xs font-bold transition-all">
                        <i class="fa-solid fa-clock-rotate-left text-orange-400 text-[10px]"></i>
                        Mon activité
                    </a>
                    <a href="{{ route('profile.sessions') }}"
                       class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-600 text-xs font-bold transition-all">
                        <i class="fa-solid fa-desktop text-blue-400 text-[10px]"></i>
                        Sessions actives
                    </a>
                    <a href="{{ route('notifications.index') }}"
                       class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-purple-50 hover:text-purple-700 text-slate-600 text-xs font-bold transition-all">
                        <i class="fa-solid fa-bell text-purple-400 text-[10px]"></i>
                        Mes notifications
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
