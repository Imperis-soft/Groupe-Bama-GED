@extends('layouts.guest')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center">
                <i class="fa-solid fa-times-circle text-6xl text-red-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Code Invalide</h2>
                <p class="text-gray-600">Le code de vérification que vous avez utilisé n'est pas valide ou a expiré.</p>
            </div>

            <div class="mt-6 text-center">
                <a href="{{ url('/') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-xl transition duration-200">
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </div>
</div>
@endsection