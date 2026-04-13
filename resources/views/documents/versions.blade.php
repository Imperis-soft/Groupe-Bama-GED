@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Versions du document</h1>
            <p class="text-xs text-slate-500 font-medium">{{ $document->title }}</p>
        </div>
        <a href="{{ route('documents.show', $document) }}" class="bg-slate-100 px-4 py-2 rounded-xl text-xs font-bold">Retour au document</a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Version</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Créé par</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Description</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($versions as $version)
                <tr class="hover:bg-slate-50/50 transition-colors {{ $version->version_number == $document->version ? 'bg-orange-50' : '' }}">
                    <td class="px-6 py-4 font-mono font-bold text-slate-900">
                        v{{ $version->version_number }}
                        @if($version->version_number == $document->version)
                            <span class="ml-2 px-2 py-1 bg-orange-100 text-orange-600 rounded text-[10px] font-bold">ACTUELLE</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-bold text-slate-900">{{ $version->creator->full_name ?? 'Système' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $version->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $version->change_description ?? 'Aucune description' }}</td>
                    <td class="px-6 py-4 text-right">
                        @if($version->version_number != $document->version)
                            <form action="{{ route('documents.versions.restore', [$document, $version->version_number]) }}" method="POST" class="inline">
                                @csrf @method('POST')
                                <button type="submit" class="text-blue-600 font-bold hover:underline">Restaurer</button>
                            </form>
                        @else
                            <span class="text-slate-400">Version actuelle</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
            {{ $versions->links() }}
        </div>
    </div>
</div>
@endsection