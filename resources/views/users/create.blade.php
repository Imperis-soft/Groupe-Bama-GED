@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-xl font-semibold mb-4">Créer un nouvel utilisateur</h1>

        @if($errors->any())
            <div class="mb-4 text-red-600">
                <ul>
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('users.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Nom complet</label>
                    <input name="full_name" value="{{ old('full_name') }}" class="w-full border p-2 rounded" required />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" class="w-full border p-2 rounded" required />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Mot de passe</label>
                    <input name="password" type="password" class="w-full border p-2 rounded" required />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Confirmer mot de passe</label>
                    <input name="password_confirmation" type="password" class="w-full border p-2 rounded" required />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Téléphone</label>
                    <input name="phone" value="{{ old('phone') }}" class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Adresse</label>
                    <input name="address" value="{{ old('address') }}" class="w-full border p-2 rounded" />
                </div>
            </div>

            <div class="mt-4">
                <button class="bg-green-600 text-white px-4 py-2 rounded">Créer</button>
                <a href="{{ route('users.index') }}" class="ml-2 text-sm text-gray-600">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
