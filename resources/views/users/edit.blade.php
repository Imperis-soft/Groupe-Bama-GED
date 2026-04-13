@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="bg-white p-6 rounded shadow">
        <h1 class="text-xl font-semibold mb-4">Éditer l'utilisateur</h1>

        @if($errors->any())
            <div class="mb-4 text-red-600">
                <ul>
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600">Nom complet</label>
                    <input name="full_name" value="{{ old('full_name', $user->full_name) }}" class="w-full border p-2 rounded" required />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Email</label>
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full border p-2 rounded" required />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Téléphone</label>
                    <input name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border p-2 rounded" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600">Adresse</label>
                    <input name="address" value="{{ old('address', $user->address) }}" class="w-full border p-2 rounded" />
                </div>
            </div>

            <div class="mt-4">
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Mettre à jour</button>
                <a href="{{ route('users.index') }}" class="ml-2 text-sm text-gray-600">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection