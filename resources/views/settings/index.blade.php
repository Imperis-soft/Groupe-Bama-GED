@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <div class="flex items-center justify-between mb-2">
        <div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight">Configuration</h1>
            <p class="text-[11px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Paramètres globaux — Groupe Bama</p>
        </div>
        <div class="w-10 h-10 bg-white border border-slate-100 rounded-xl flex items-center justify-center text-slate-400 shadow-sm">
            <i class="fa-solid fa-sliders text-xs"></i>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-[1.5rem] border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center gap-3">
                <i class="fa-solid fa-microchip text-orange-600 text-[10px]"></i>
                <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Identité de la plateforme</h2>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <label class="text-[11px] font-black text-slate-700 uppercase">Nom du Site</label>
                        <p class="text-[10px] text-slate-400 font-medium leading-relaxed mt-1 italic">Label affiché sur l'interface.</p>
                    </div>
                    <div class="md:col-span-2">
                        <input type="text" name="app_name" 
                               value="{{ $settings['app_name'] ?? config('app.name') }}"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/5 outline-none transition-all">
                    </div>
                </div>

                <div class="h-px bg-slate-50 w-full"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                    <div>
                        <label class="text-[11px] font-black text-slate-700 uppercase">URL de Base</label>
                        <p class="text-[10px] text-slate-400 font-medium leading-relaxed mt-1 italic">Crucial pour les liaisons WebDAV Word.</p>
                    </div>
                    <div class="md:col-span-2">
                        <div class="relative group">
                            <i class="fa-solid fa-link absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-[10px]"></i>
                            <input type="text" name="app_url" 
                                   value="{{ $settings['app_url'] ?? config('app.url') }}"
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-mono font-bold text-orange-600 focus:bg-white focus:border-orange-500 outline-none transition-all">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-900 rounded-[1.5rem] p-6 text-white border border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 opacity-10">
                <i class="fa-solid fa-shield-halved text-8xl"></i>
            </div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-900/20">
                        <i class="fa-solid fa-check-double text-xs"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black tracking-tight">Vérification Requise</h3>
                        <p class="text-[10px] text-slate-400 font-medium mt-0.5">L'URL doit correspondre au domaine configuré sur Imperis Sarl.</p>
                    </div>
                </div>
                
                <button type="submit" class="bg-white text-slate-900 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 hover:text-white transition-all transform active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    Appliquer les changements
                </button>
            </div>
        </div>
    </form>

    <div class="flex items-center justify-center gap-4 py-4 opacity-50">
        <span class="h-px bg-slate-200 flex-1"></span>
        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic">Solution gérée par Imperis Sarl</p>
        <span class="h-px bg-slate-200 flex-1"></span>
    </div>
</div>
@endsection