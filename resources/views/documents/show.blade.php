@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">{{ $document->title }}</h1>
                <p class="text-xs text-slate-500 font-medium">Référence: {{ $document->reference }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('documents.edit-online', $document) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg shadow-blue-100 transition-all">
                    <i class="fa-solid fa-edit"></i> Éditer en ligne
                </a>
                <a href="ms-word:ofe|u|{{ url('/webdav/' . $document->id) }}" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg shadow-orange-100 transition-all" title="Nécessite HTTPS en production">
                    <i class="fa-solid fa-pen"></i> Éditer dans Word
                </a>
                <a href="{{ route('documents.edit', $document) }}" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-xl text-xs font-bold">
                    Modifier
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">Catégorie</h3>
                <p class="text-slate-900 font-medium">{{ $document->category?->name ?? 'Général' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">Créé le</h3>
                <p class="text-slate-900 font-medium">{{ $document->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">Créé par</h3>
                <p class="text-slate-900 font-medium">{{ $document->creator?->full_name ?? 'Inconnu' }}</p>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">Version</h3>
                <p class="text-slate-900 font-medium">{{ $document->version }}</p>
            </div>
        </div>

        @if($document->tags)
        <div class="mb-6">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">Tags</h3>
            <div class="flex flex-wrap gap-2">
                @php
                    $tagsArray = is_array($document->tags) ? $document->tags : explode(',', $document->tags);
                @endphp
                @foreach($tagsArray as $tag)
                    <span class="px-2 py-1 bg-slate-100 rounded text-xs font-bold text-slate-600">{{ trim($tag) }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if($document->metadata)
        <div class="mb-6">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-2">Métadonnées</h3>
            <pre class="bg-slate-50 p-4 rounded-xl text-xs font-mono text-slate-700">{{ json_encode($document->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif

        <div class="flex gap-3">
            <a href="{{ route('documents.index') }}" class="bg-slate-100 px-4 py-2 rounded-xl text-xs font-bold">Retour à la liste</a>
            <a href="{{ route('documents.versions', $document) }}" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-xl text-xs font-bold">Versions</a>
            <a href="{{ route('documents.audit', $document) }}" class="bg-purple-100 text-purple-700 px-4 py-2 rounded-xl text-xs font-bold">Audit</a>
            @if(!$document->isArchived())
                <form action="{{ route('documents.archive', $document) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-orange-100 text-orange-700 px-4 py-2 rounded-xl text-xs font-bold">Archiver</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection