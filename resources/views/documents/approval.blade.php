@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Approbation</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Workflow d'approbation</h1>
        </div>
        <a href="{{ route('documents.show', $document) }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- Étapes actuelles --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Étapes d'approbation</h2>
            </div>

            @if($steps->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-10 h-10 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-list-check text-slate-300 text-lg"></i>
                </div>
                <p class="text-xs font-bold text-slate-400">Aucun workflow configuré</p>
            </div>
            @else
            <div class="divide-y divide-slate-50">
                @foreach($steps as $step)
                <div class="flex items-start gap-4 px-5 py-4">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 font-black text-xs
                        {{ $step->status === 'approved' ? 'bg-green-100 text-green-600' :
                           ($step->status === 'rejected' ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-500') }}">
                        {{ $step->step_order }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-bold text-slate-800">{{ $step->approver->full_name }}</p>
                            <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase
                                {{ $step->status === 'approved' ? 'bg-green-50 text-green-600' :
                                   ($step->status === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600') }}">
                                {{ $step->status }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $step->approver->email }}</p>
                        @if($step->comment)
                        <p class="text-xs text-slate-600 mt-1 italic bg-slate-50 rounded-lg px-3 py-2">{{ $step->comment }}</p>
                        @endif
                        @if($step->decided_at)
                        <p class="text-[9px] text-slate-400 mt-1">Décidé le {{ $step->decided_at->format('d/m/Y à H:i') }}</p>
                        @endif

                        {{-- Actions pour l'approbateur courant --}}
                        @if($step->isPending() && ($step->approver_id === auth()->id() || auth()->user()->hasRole('admin')))
                        <div class="flex gap-2 mt-3" x-data="{ showReject: false }">
                            <form action="{{ route('documents.approval.approve', [$document, $step]) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-500 text-white text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                                    <i class="fa-solid fa-check text-[8px]"></i> Approuver
                                </button>
                            </form>
                            <button @click="showReject = !showReject"
                                class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-[10px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                                <i class="fa-solid fa-xmark text-[8px]"></i> Rejeter
                            </button>
                            <div x-show="showReject" x-cloak class="w-full mt-2">
                                <form action="{{ route('documents.approval.reject', [$document, $step]) }}" method="POST" class="flex gap-2">
                                    @csrf
                                    <input type="text" name="reason" required placeholder="Raison du rejet..."
                                           class="flex-1 bg-slate-50 border border-slate-100 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <button type="submit"
                                        class="bg-red-600 text-white text-[10px] font-black px-3 py-1.5 rounded-lg hover:bg-red-500 transition-all">
                                        Confirmer
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Configurer le workflow --}}
        @if(auth()->user()->hasRole('admin') || $document->creator_id === auth()->id())
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest mb-4">Configurer le workflow</h2>
            <form action="{{ route('documents.approval.setup', $document) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Approbateurs (dans l'ordre)</label>
                    <select name="approvers[]" multiple required
                            class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 h-32">
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[9px] text-slate-400 mt-1">Ctrl+clic pour sélectionner plusieurs</p>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Délai (jours)</label>
                    <input type="number" name="due_days" min="1" max="365" placeholder="Ex: 7"
                           class="w-full bg-slate-50 border border-slate-100 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <button type="submit"
                    class="w-full bg-orange-600 hover:bg-orange-500 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-200 transition-all active:scale-95">
                    <i class="fa-solid fa-paper-plane mr-1.5"></i> Lancer le workflow
                </button>
            </form>
        </div>
        @endif

    </div>
</div>
@endsection
