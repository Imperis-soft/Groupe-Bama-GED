@extends('layouts.app')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
    <div class="w-20 h-20 rounded-3xl bg-red-50 flex items-center justify-center mb-6 shadow-sm">
        <i class="fa-solid fa-lock text-red-400 text-3xl"></i>
    </div>
    <h1 class="text-6xl font-black text-slate-900 leading-none mb-2">403</h1>
    <p class="text-lg font-black text-slate-700 mb-1">Accès refusé</p>
    <p class="text-sm text-slate-400 font-medium max-w-sm mb-8">
        {{ $exception->getMessage() ?: "Vous n'avez pas la permission d'accéder à cette ressource." }}
    </p>
    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-3 rounded-xl shadow-lg shadow-orange-200 transition-all">
            <i class="fa-solid fa-house text-[10px]"></i> Tableau de bord
        </a>
        <button onclick="history.back()"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-5 py-3 rounded-xl shadow-sm transition-all">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </button>
    </div>
</div>
@endsection
