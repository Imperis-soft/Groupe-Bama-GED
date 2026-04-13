@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Mes favoris</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $documents->total() }} document(s) épinglé(s)</p>
        </div>
        <a href="{{ route('documents.index') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Tous les documents
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if($documents->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-star text-amber-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucun favori</p>
            <p class="text-xs text-slate-300 mt-1">Épinglez des documents depuis leur page pour les retrouver ici</p>
        </div>
        @else
        <div class="divide-y divide-slate-50">
            @foreach($documents as $doc)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50/60 transition-colors group">
                <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                    <i class="fa-solid fa-file-word text-orange-500 text-sm group-hover:text-white transition-colors"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('documents.show', $doc) }}"
                       class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate block transition-colors">
                        {{ $doc->title }}
                    </a>
                    <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                        {{ $doc->reference }} · {{ $doc->category?->name ?? 'Général' }}
                    </p>
                </div>
                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase shrink-0
                    {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                       ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600') }}">
                    {{ $doc->status }}
                </span>
                <form action="{{ route('documents.favorite', $doc) }}" method="POST" class="inline shrink-0">
                    @csrf
                    <button type="submit" title="Retirer des favoris"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-amber-400 hover:bg-red-50 hover:text-red-500 transition-all">
                        <i class="fa-solid fa-star text-xs"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @if($documents->hasPages())
        <div class="px-5 py-4 border-t border-slate-50">{{ $documents->links() }}</div>
        @endif
        @endif
    </div>
</div>
@endsection
