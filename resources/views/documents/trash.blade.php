@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Corbeille</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $documents->total() }} document(s) supprimé(s)</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            @if(auth()->user()->hasRole('admin') && $documents->total() > 0)
            <form action="{{ route('trash.empty') }}" method="POST"
                  onsubmit="return confirm('Vider définitivement la corbeille ? Cette action est irréversible.');">
                @csrf @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-500 text-white text-xs font-black uppercase tracking-widest px-4 py-2.5 rounded-xl shadow-lg shadow-red-200 transition-all active:scale-95">
                    <i class="fa-solid fa-trash text-[10px]"></i> Vider la corbeille
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if($documents->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-trash-can text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">La corbeille est vide</p>
        </div>
        @else
        <div class="divide-y divide-slate-50">
            @foreach($documents as $doc)
            <div class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50/60 transition-colors">
                <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-word text-red-400 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-700 truncate">{{ $doc->title }}</p>
                    <p class="text-[10px] text-slate-400 font-mono mt-0.5">
                        {{ $doc->reference }} · Supprimé {{ $doc->deleted_at->diffForHumans() }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form action="{{ route('trash.restore', $doc->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-green-50 hover:bg-green-100 text-green-600 text-[9px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                            <i class="fa-solid fa-rotate-left text-[8px]"></i> Restaurer
                        </button>
                    </form>
                    @if(auth()->user()->hasRole('admin'))
                    <form action="{{ route('trash.force-delete', $doc->id) }}" method="POST"
                          onsubmit="return confirm('Supprimer définitivement ?');" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-all">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                    </form>
                    @endif
                </div>
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
