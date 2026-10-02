@extends('layouts.guest')

@section('title', 'Document non reconnu')

@section('content')
<meta name="referrer" content="no-referrer">
<div class="min-h-screen bg-slate-100 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-6 flex items-center gap-4">
            <i class="fas fa-circle-xmark text-4xl"></i>
            <div>
                <h1 class="text-xl font-bold tracking-wide">DOCUMENT NON RECONNU</h1>
                <p class="text-red-50 text-sm">Ce code ne correspond à aucun document enregistré.</p>
            </div>
        </div>
        <div class="p-6 space-y-4 text-sm text-slate-600">
            <p>Le document présenté n'a pas été émis par cette GED, ou son QR code a été altéré. <strong>Ne vous y fiez pas.</strong></p>
            <ul class="space-y-2">
                <li><i class="fas fa-angle-right text-slate-400 mr-2"></i>Essayez de saisir le code imprimé en pied de page sur la page de vérification manuelle.</li>
                <li><i class="fas fa-angle-right text-slate-400 mr-2"></i>Contactez l'émetteur du document pour confirmation.</li>
            </ul>
            <a href="{{ route('verification.lookup') }}" class="block text-center bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl py-3 transition">
                Saisir un code de vérification
            </a>
        </div>
    </div>
</div>
@endsection
