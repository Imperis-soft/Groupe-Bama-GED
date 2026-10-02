{{-- Cycle de vie du document : où il en est, ce qui est obligatoire, et retrait du circuit (motif tracé) --}}
@php
    $wfRule   = $document->workflowRule();
    $wfStages = [
        'draft'   => ['Brouillon', 'fa-pen-to-square'],
        'review'  => ['Approbation', 'fa-list-check'],
        'approved'=> ['Approuvé', 'fa-circle-check'],
        'signing' => ['Signature', 'fa-signature'],
        'signed'  => ['Signé', 'fa-file-signature'],
    ];
    $wfKeys    = array_keys($wfStages);
    $wfCurrent = array_search($document->status === 'archived' ? ($document->status_before_archive ?: 'approved') : $document->status, $wfKeys, true);
    $wfCanWithdraw = $document->isInWorkflow() && $document->canManage();
@endphp

@if($document->status !== 'archived' && ($wfRule['approval'] || $wfRule['signature'] || $document->status !== 'draft'))
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-5 py-4 space-y-3" x-data="{ withdraw: {{ $errors->has('reason') && old('_withdraw') ? 'true' : 'false' }} }">
    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto">
        @foreach($wfStages as $key => [$label, $icon])
        @php $i = $loop->index; $done = $wfCurrent !== false && $i < $wfCurrent; $here = $i === $wfCurrent; @endphp
        @if(!$loop->first)<div class="h-px flex-1 min-w-3 {{ $done || $here ? 'bg-orange-300' : 'bg-slate-100' }}"></div>@endif
        <div class="flex items-center gap-1.5 shrink-0 px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider
            {{ $here ? 'bg-orange-600 text-white' : ($done ? 'text-orange-600' : 'text-slate-300') }}">
            <i class="fa-solid {{ $icon }} text-[9px]"></i>{{ $label }}
        </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2 text-[11px]">
        @if($wfRule['approval'])
        <span class="inline-flex items-center gap-1 font-bold text-red-700 bg-red-50 px-2 py-1 rounded-lg"><i class="fa-solid fa-lock text-[9px]"></i>Approbation obligatoire</span>
        @endif
        @if($wfRule['signature'])
        <span class="inline-flex items-center gap-1 font-bold text-red-700 bg-red-50 px-2 py-1 rounded-lg"><i class="fa-solid fa-lock text-[9px]"></i>Signature obligatoire</span>
        @endif
        @if($document->isInWorkflow())
        <span class="inline-flex items-center gap-1 font-bold text-blue-700 bg-blue-50 px-2 py-1 rounded-lg"><i class="fa-solid fa-snowflake text-[9px]"></i>Contenu gelé pendant le circuit (version {{ $document->version }})</span>
        @elseif($document->status === 'draft' && $wfRule['approval'])
        <span class="text-slate-500">Ce document n'a pas de valeur officielle tant que le circuit d'approbation n'est pas terminé.</span>
        @elseif($document->status === 'approved' && $wfRule['signature'])
        <span class="text-slate-500">Approuvé : les signatures doivent maintenant être demandées.</span>
        @endif

        @if($wfCanWithdraw)
        <button type="button" @click="withdraw = !withdraw" class="ml-auto inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500 hover:text-red-600 bg-slate-50 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-all">
            <i class="fa-solid fa-arrow-rotate-left text-[9px]"></i> Retirer du circuit
        </button>
        @endif
    </div>

    @if($wfCanWithdraw)
    <form x-show="withdraw" x-cloak action="{{ route('documents.workflow.withdraw', $document) }}" method="POST" class="bg-red-50 rounded-xl p-3 space-y-2">
        @csrf
        <input type="hidden" name="_withdraw" value="1">
        <p class="text-[11px] text-red-800">
            Le document repassera en brouillon et redeviendra modifiable. Les étapes et demandes de signature en attente seront closes,
            les personnes concernées prévenues, et le motif inscrit au journal.
        </p>
        <div class="flex gap-2">
            <input type="text" name="reason" required minlength="5" value="{{ old('reason') }}" placeholder="Motif du retrait (obligatoire)"
                   class="flex-1 bg-white border border-red-200 rounded-lg px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500">
            <button type="submit" class="bg-red-600 hover:bg-red-500 text-white text-[10px] font-black uppercase px-4 py-2 rounded-lg">Retirer</button>
        </div>
        @error('reason')<p class="text-[11px] text-red-600 font-bold">{{ $message }}</p>@enderror
    </form>
    @endif
</div>
@endif
