@extends('layouts.guest')

@section('title', 'Vérifier un document')

@section('content')
<meta name="referrer" content="no-referrer">
<div class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
        <div class="text-center mb-6">
            <div class="bg-orange-600 text-white w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shield-halved text-2xl"></i>
            </div>
            <h1 class="text-xl font-bold text-slate-800">Vérifier un document</h1>
            <p class="text-sm text-slate-500 mt-1">Saisissez le code imprimé à côté du QR code, en pied de page.</p>
        </div>

        @if($notFound)
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3">
                <i class="fas fa-circle-xmark mr-2"></i>Aucun document ne correspond à ce code. Vérifiez la saisie ; s'il est exact, le document n'est pas authentique.
            </div>
        @endif

        <form method="GET" action="{{ route('verification.lookup') }}" class="space-y-4">
            <input type="text" name="code" required autocomplete="off" autocapitalize="characters" spellcheck="false"
                   value="{{ request('code') }}" placeholder="XXXX-XXXX-XXXX" maxlength="80"
                   class="w-full text-center font-mono text-lg tracking-widest uppercase rounded-xl border border-slate-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-orange-500">
            <button class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl py-3 transition">
                <i class="fas fa-magnifying-glass mr-2"></i>Vérifier
            </button>
        </form>
    </div>
</div>
@endsection
