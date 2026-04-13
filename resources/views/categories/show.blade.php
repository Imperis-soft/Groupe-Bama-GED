@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">{{ $category->name }}</h1>
                <p class="text-xs text-gray-500 font-medium">Slug: {{ $category->slug }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('categories.edit', $category) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold">
                    Modifier
                </a>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Description</h3>
            <p class="text-gray-900 font-medium">{{ $category->description ?? 'Aucune description.' }}</p>
        </div>

        <div class="mb-6">
            <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Documents dans cette catégorie</h3>
            <p class="text-gray-600">{{ $category->documents->count() }} document(s)</p>
            @if($category->documents->count() > 0)
                <ul class="mt-2 space-y-1">
                    @foreach($category->documents as $doc)
                        <li><a href="{{ route('documents.show', $doc) }}" class="text-blue-600 hover:underline">{{ $doc->title }}</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="flex gap-3">
            <a href="{{ route('categories.index') }}" class="bg-gray-100 px-4 py-2 rounded-xl text-xs font-bold">Retour à la liste</a>
        </div>
    </div>
</div>
@endsection