@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- BREADCRUMB + HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1 flex-wrap">
                <a href="{{ route('categories.index') }}" class="hover:text-orange-600 transition-colors">Catégories</a>
                @if($category->parent)
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <a href="{{ route('categories.show', $category->parent) }}" class="hover:text-orange-600 transition-colors">
                    {{ $category->parent->name }}
                </a>
                @endif
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">{{ $category->name }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $category->name }}</h1>
            <p class="text-[10px] font-mono text-orange-500 font-bold mt-1">{{ $category->slug }}</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            @if(auth()->user()->hasRole('admin'))
            <a href="{{ route('categories.edit', $category) }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-pen text-[10px]"></i> Modifier
            </a>
            @endif
            <a href="{{ route('categories.index') }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
            </a>
        </div>
    </div>

    {{-- HERO CARD --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="h-1.5 w-full bg-gradient-to-r from-orange-400 to-orange-600"></div>
        <div class="p-5 md:p-6 flex flex-col md:flex-row md:items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-orange-50 flex items-center justify-center shrink-0 border border-orange-100">
                <i class="fa-solid fa-folder-tree text-orange-500 text-3xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                @if($category->description)
                <p class="text-sm text-slate-600 leading-relaxed mb-3">{{ $category->description }}</p>
                @endif
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-100 rounded-lg px-3 py-1.5">
                        <i class="fa-solid fa-file-lines text-orange-400 text-[10px]"></i>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-wider">{{ $documents->total() }} document(s)</span>
                    </div>
                    @if($category->children->count() > 0)
                    <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-100 rounded-lg px-3 py-1.5">
                        <i class="fa-solid fa-sitemap text-purple-400 text-[10px]"></i>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-wider">{{ $category->children->count() }} sous-catégorie(s)</span>
                    </div>
                    @endif
                    @if($category->parent)
                    <div class="flex items-center gap-1.5 bg-orange-50 border border-orange-100 rounded-lg px-3 py-1.5">
                        <i class="fa-solid fa-folder text-orange-400 text-[10px]"></i>
                        <span class="text-[10px] font-black text-orange-600 uppercase tracking-wider">{{ $category->parent->name }}</span>
                    </div>
                    @endif
                </div>
            </div>
            {{-- Action rapide --}}
            <a href="{{ route('documents.index', ['category' => $category->id]) }}"
               class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all shrink-0">
                <i class="fa-solid fa-filter text-[10px]"></i>
                <span class="hidden sm:inline">Filtrer les docs</span>
            </a>
        </div>
    </div>

    {{-- SOUS-CATÉGORIES --}}
    @if($category->children->count() > 0)
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Sous-catégories</h2>
        </div>
        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            @foreach($category->children as $child)
            <a href="{{ route('categories.show', $child) }}"
               class="flex flex-col items-center gap-2 p-3 bg-slate-50 hover:bg-orange-50 hover:border-orange-200 border border-slate-100 rounded-xl transition-all group text-center">
                <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center border border-slate-100 group-hover:bg-orange-600 group-hover:border-orange-600 transition-colors">
                    <i class="fa-solid fa-folder text-slate-400 text-sm group-hover:text-white transition-colors"></i>
                </div>
                <p class="text-[10px] font-bold text-slate-700 group-hover:text-orange-600 transition-colors leading-tight">{{ $child->name }}</p>
                <span class="text-[8px] font-black text-slate-400 uppercase">{{ $child->documents_count }} doc(s)</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- DOCUMENTS --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">
                    Documents <span class="text-slate-400 font-normal">({{ $documents->total() }})</span>
                </h2>
            </div>
            <a href="{{ route('documents.index', ['category' => $category->id]) }}"
               class="text-[10px] font-bold text-slate-400 hover:text-orange-600 transition-colors">
                Voir tous <i class="fa-solid fa-arrow-right text-[8px]"></i>
            </a>
        </div>

        @if($documents->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-inbox text-slate-300 text-lg"></i>
            </div>
            <p class="text-xs font-bold text-slate-400">Aucun document dans cette catégorie</p>
        </div>
        @else

        {{-- Desktop --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/60">
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Document</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Statut</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Créateur</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($documents as $doc)
                    <tr class="hover:bg-slate-50/60 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                                    <i class="fa-solid fa-file-word text-orange-500 text-xs group-hover:text-white transition-colors"></i>
                                </div>
                                <div>
                                    <a href="{{ route('documents.show', $doc) }}"
                                       class="text-sm font-bold text-slate-800 hover:text-orange-600 transition-colors">
                                        {{ $doc->title }}
                                    </a>
                                    <p class="text-[9px] font-mono text-slate-400 mt-0.5">{{ $doc->reference }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider
                                {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                                   ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' :
                                   ($doc->status === 'archived' ? 'bg-slate-100 text-slate-400' : 'bg-amber-50 text-amber-600')) }}">
                                {{ $doc->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-medium text-slate-600">{{ $doc->creator?->full_name ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-slate-700">{{ $doc->created_at->format('d/m/Y') }}</p>
                            <p class="text-[9px] text-slate-400">{{ $doc->created_at->diffForHumans() }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('documents.show', $doc) }}"
                               class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-orange-600 hover:text-white text-slate-600 text-[9px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                                <i class="fa-solid fa-eye text-[8px]"></i> Voir
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden divide-y divide-slate-50">
            @foreach($documents as $doc)
            <a href="{{ route('documents.show', $doc) }}"
               class="flex items-center gap-3 px-4 py-4 hover:bg-slate-50/60 transition-colors group">
                <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center shrink-0 group-hover:bg-orange-600 transition-colors">
                    <i class="fa-solid fa-file-word text-orange-500 text-sm group-hover:text-white transition-colors"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate">{{ $doc->title }}</p>
                    <p class="text-[9px] font-mono text-slate-400 mt-0.5">{{ $doc->reference }} · {{ $doc->created_at->format('d/m/Y') }}</p>
                </div>
                <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase shrink-0
                    {{ $doc->status === 'approved' ? 'bg-green-50 text-green-600' :
                       ($doc->status === 'review'   ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600') }}">
                    {{ $doc->status }}
                </span>
            </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($documents->hasPages())
        <div class="px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                {{ $documents->firstItem() }}–{{ $documents->lastItem() }} sur {{ $documents->total() }} documents
            </p>
            {{ $documents->links() }}
        </div>
        @endif

        @endif
    </div>

</div>
@endsection
