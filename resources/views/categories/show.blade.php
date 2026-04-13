@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('categories.index') }}" class="hover:text-orange-600 transition-colors">Catégories</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">{{ $category->name }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $category->name }}</h1>
            <p class="text-[10px] font-mono text-orange-500 font-bold mt-1">{{ $category->slug }}</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('categories.edit', $category) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-pen text-[10px]"></i> Modifier
            </a>
            <a href="{{ route('categories.index') }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        {{-- Infos --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Informations</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Nom</p>
                    <p class="text-sm font-bold text-slate-800">{{ $category->name }}</p>
                </div>
                <div class="h-px bg-slate-50"></div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Slug</p>
                    <p class="text-sm font-mono font-bold text-orange-500">{{ $category->slug }}</p>
                </div>
                <div class="h-px bg-slate-50"></div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Documents</p>
                    <p class="text-2xl font-black text-slate-900">{{ $category->documents->count() }}</p>
                </div>
                @if($category->description)
                <div class="h-px bg-slate-50"></div>
                <div>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Description</p>
                    <p class="text-xs text-slate-600 leading-relaxed">{{ $category->description }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Documents --}}
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Documents</h2>
                </div>
                <a href="{{ route('documents.index') }}?category={{ $category->id }}"
                   class="text-[10px] font-bold text-slate-400 hover:text-orange-600 transition-colors">
                    Voir tous <i class="fa-solid fa-arrow-right text-[8px]"></i>
                </a>
            </div>

            @if($category->documents->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-inbox text-slate-300 text-lg"></i>
                </div>
                <p class="text-xs font-bold text-slate-400">Aucun document dans cette catégorie</p>
            </div>
            @else
            <div class="divide-y divide-slate-50">
                @foreach($category->documents as $doc)
                <a href="{{ route('documents.show', $doc) }}"
                   class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50/60 transition-colors group">
                    <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                        <i class="fa-solid fa-file-word text-orange-500 text-xs group-hover:text-white transition-colors"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-800 truncate">{{ $doc->title }}</p>
                        <p class="text-[9px] font-mono text-slate-400 mt-0.5">{{ $doc->reference }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase shrink-0
                        {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                           ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' :
                           ($doc->status === 'archived' ? 'bg-slate-100 text-slate-400' : 'bg-amber-50 text-amber-600')) }}">
                        {{ $doc->status }}
                    </span>
                </a>
                @endforeach
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
