@extends('layouts.app')

@section('content')
<div class="fixed inset-0 flex flex-col md:flex-row bg-gray-50 overflow-hidden">
    
    <div class="hidden md:flex md:w-1/2 lg:w-3/5 bg-orange-600 relative items-center justify-center p-12 overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <svg width="100%" height="100%"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(#grid)" /></svg>
        </div>
        
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-orange-800/20 rounded-full blur-3xl"></div>

        <div class="relative z-10 max-w-lg text-center md:text-left">
            <div class="mb-8 inline-flex items-center justify-center w-20 h-20 bg-white rounded-[2rem] shadow-2xl shadow-orange-900/20 rotate-12 transition-transform hover:rotate-0 duration-500">
                <i class="fa-solid fa-briefcase text-orange-600 text-4xl"></i>
            </div>
            <h1 class="text-5xl lg:text-6xl font-black text-white leading-tight mb-6">
                GED <br><span class="text-orange-200">GROUPE BAMA</span>
            </h1>
            <p class="text-orange-100 text-lg font-medium leading-relaxed opacity-90">
                Gestion Électronique de Documents sécurisée. <br>
                Accédez à vos ressources critiques en un clic avec une édition native Microsoft Word.
            </p>
            
            <div class="mt-12 flex items-center gap-6">
                <div class="flex -space-x-3">
                    <div class="w-10 h-10 rounded-full border-2 border-orange-600 bg-orange-400"></div>
                    <div class="w-10 h-10 rounded-full border-2 border-orange-600 bg-orange-300"></div>
                    <div class="w-10 h-10 rounded-full border-2 border-orange-600 bg-white flex items-center justify-center text-[10px] font-black text-orange-600">+12</div>
                </div>
                <p class="text-sm text-orange-100 font-bold tracking-wide uppercase">Collaborateurs actifs</p>
            </div>
        </div>

        <div class="absolute bottom-10 left-10 text-white/40 text-[10px] font-black tracking-[0.3em] uppercase">
            System Version 2.0.4 — © {{ date("Y") }} Groupe Bama
        </div>
    </div>

    <div class="w-full md:w-1/2 lg:w-2/5 bg-white flex items-center justify-center p-8 md:p-16 lg:p-24 relative">
        <div class="w-full max-w-sm animate-fade-up">
            
            <div class="mb-10 text-center md:text-left">
                <div class="md:hidden mb-6 inline-flex w-12 h-12 bg-orange-600 text-white rounded-xl items-center justify-center text-xl shadow-lg shadow-orange-200">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight mb-2">Connexion</h2>
                <p class="text-gray-400 text-sm font-medium italic">Veuillez entrer vos identifiants d'accès.</p>
            </div>

            <form action="{{ route('login') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] ml-1">Email Professionnel</label>
                    <div class="relative group">
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:bg-white focus:border-orange-500 focus:ring-0 transition-all outline-none placeholder:text-gray-300"
                               placeholder="nom@groupebama.com">
                        <i class="fa-solid fa-envelope absolute right-6 top-1/2 -translate-y-1/2 text-gray-300 group-focus-within:text-orange-500 transition-colors"></i>
                    </div>
                    @error('email') <p class="text-red-500 text-[10px] font-bold mt-1 ml-2 uppercase">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-end px-1">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Mot de passe</label>
                        <!-- <a href="#" class="text-[10px] font-black text-orange-600 hover:underline uppercase tracking-tighter">Oublié ?</a> -->
                    </div>
                    <div class="relative group" x-data="{ show: false }">
                        <input :type="show ? 'text' : 'password'" name="password" required
                               class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 focus:bg-white focus:border-orange-500 focus:ring-0 transition-all outline-none">
                        <button type="button" @click="show = !show" class="absolute right-6 top-1/2 -translate-y-1/2 text-gray-300 hover:text-orange-500 transition-colors">
                            <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between py-2">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" name="remember" class="sr-only peer">
                            <div class="w-5 h-5 bg-gray-100 border-2 border-gray-100 rounded-md peer-checked:bg-orange-600 peer-checked:border-orange-600 transition-all"></div>
                            <i class="fa-solid fa-check absolute inset-0 text-[10px] text-white flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                        </div>
                        <span class="text-xs font-bold text-gray-500 group-hover:text-gray-700 transition-colors">Rester connecté</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-orange-600 text-white py-5 rounded-[1.25rem] font-black text-sm shadow-xl shadow-orange-200 hover:bg-orange-700 hover:shadow-orange-300/50 transition-all transform active:scale-[0.98] flex items-center justify-center gap-3">
                    SE CONNECTER AU SYSTÈME
                    <i class="fa-solid fa-arrow-right-long animate-pulse"></i>
                </button>
            </form>

            <div class="mt-12 text-center">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    Besoin d'aide ? <a href="mailto:contact@imperis.com" class="text-orange-600 hover:underline">Contacter l'IT</a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes fade-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-up { animation: fade-up 0.6s ease-out forwards; }
    
    /* On cache le header/footer standard sur cette page si nécessaire */
    nav, footer { display: none !important; }
</style>
@endsection