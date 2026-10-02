@extends('layouts.app')

@section('content')
@php
    $todoCount = $approvals->count() + $signatures->count() + $toFix->count();
    $newShares = $shares->whereNull('accessed_at')->count();
    $followCount = $myApprovals->count() + $mySignatureRequests->where('status', 'pending')->count();

    // Badge d'échéance : rouge si dépassée, orange si dans les 2 jours
    $dueBadge = function ($dueAt) {
        if (!$dueAt) return null;
        if ($dueAt->isPast()) return ['bg-red-50 text-red-600', 'En retard · ' . $dueAt->format('d/m/Y')];
        if ($dueAt->lte(now()->addDays(2))) return ['bg-amber-50 text-amber-600', 'Échéance ' . $dueAt->diffForHumans()];
        return ['bg-slate-50 text-slate-500', 'Avant le ' . $dueAt->format('d/m/Y')];
    };
@endphp

<div class="space-y-5"
     x-data="{
        tab: ['todo', 'shared', 'follow'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'todo',
        modal: { open: false, action: '', title: '', label: '', field: 'reason' },
        openModal(action, title, label, field = 'reason') { this.modal = { open: true, action, title, label, field }; },
     }"
     x-init="$watch('tab', t => history.replaceState(null, '', '#' + t))">

    {{-- HEADER --}}
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">À traiter</h1>
        <p class="text-xs text-slate-400 font-medium mt-1">
            @if($todoCount > 0)
                {{ $todoCount }} élément(s) attendent votre action
            @else
                Vous êtes à jour
            @endif
        </p>
    </div>

    {{-- ONGLETS --}}
    <div class="flex gap-1 bg-slate-100 rounded-xl p-1 w-full sm:w-fit overflow-x-auto">
        @foreach([
            ['todo', 'fa-list-check', 'À traiter', $todoCount],
            ['shared', 'fa-share-nodes', 'Partagés avec moi', $newShares],
            ['follow', 'fa-paper-plane', 'Suivi de mes demandes', $followCount],
        ] as [$key, $icon, $label, $count])
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-all">
            <i class="fa-solid {{ $icon }} text-[11px]"></i> {{ $label }}
            @if($count > 0)
            <span class="bg-orange-500 text-white text-[9px] font-black rounded-full px-1.5 py-0.5 leading-none">{{ $count }}</span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- ===================== À TRAITER ===================== --}}
    <div x-show="tab === 'todo'" class="space-y-5">

        @if($todoCount === 0)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-check-double text-green-500 text-xl"></i>
            </div>
            <p class="text-sm font-black text-slate-700">Rien à traiter pour le moment</p>
            <p class="text-xs text-slate-400 mt-1">Les approbations et signatures qui vous sont demandées apparaîtront ici.</p>
            @if($upcomingApprovals > 0)
            <p class="text-[11px] text-slate-400 mt-3">
                <i class="fa-solid fa-hourglass-half mr-1"></i>
                {{ $upcomingApprovals }} approbation(s) vous seront soumises après validation des étapes précédentes.
            </p>
            @endif
        </div>
        @endif

        {{-- Approbations --}}
        @if($approvals->isNotEmpty())
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-2 px-5 py-4 border-b border-slate-50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Approbations ({{ $approvals->count() }})</h2>
                </div>
                @if($upcomingApprovals > 0)
                <span class="text-[10px] text-slate-400">+ {{ $upcomingApprovals }} à venir</span>
                @endif
            </div>
            <div class="divide-y divide-slate-50">
                @foreach($approvals as $step)
                @php $doc = $step->document; $badge = $dueBadge($step->due_at); @endphp
                <div class="flex flex-col md:flex-row md:items-center gap-3 px-5 py-4">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-circle-check text-blue-500 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('documents.show', $doc) }}" class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate block">{{ $doc->title }}</a>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-[10px] text-slate-400">
                                <span class="font-mono">{{ $doc->reference }}</span>
                                @if($doc->category)<span><i class="fa-solid fa-folder mr-1"></i>{{ $doc->category->name }}</span>@endif
                                <span><i class="fa-solid fa-user mr-1"></i>{{ $doc->creator?->full_name ?? '—' }}</span>
                                <span>Étape {{ $step->step_order }}/{{ $doc->approvalSteps->count() }}</span>
                                @if($step->delegated_from_id)<span class="text-purple-600 font-bold"><i class="fa-solid fa-user-clock mr-1"></i>En remplacement de {{ $step->delegatedFrom?->full_name }}</span>@endif
                                @if($badge)<span class="px-1.5 py-0.5 rounded font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>@endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 md:pl-3">
                        <a href="{{ route('documents.preview', $doc) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-black uppercase px-3 py-2 rounded-lg transition-all">
                            <i class="fa-solid fa-eye text-[9px]"></i> Lire
                        </a>
                        <form method="POST" action="{{ route('documents.approval.approve', [$doc, $step]) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-black uppercase px-3 py-2 rounded-lg transition-all active:scale-95">
                                <i class="fa-solid fa-check text-[9px]"></i> Approuver
                            </button>
                        </form>
                        <button type="button"
                            @click="openModal(@js(route('documents.approval.reject', [$doc, $step])), @js('Rejeter « ' . $doc->title . ' »'), 'Motif du rejet (envoyé au créateur)')"
                            class="inline-flex items-center gap-1.5 bg-white border border-red-200 hover:bg-red-50 text-red-600 text-[10px] font-black uppercase px-3 py-2 rounded-lg transition-all">
                            <i class="fa-solid fa-xmark text-[9px]"></i> Rejeter
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- Signatures demandées --}}
        @if($signatures->isNotEmpty())
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Signatures demandées ({{ $signatures->count() }})</h2>
            </div>
            <div class="divide-y divide-slate-50">
                @foreach($signatures as $sr)
                @php $doc = $sr->document; $badge = $dueBadge($sr->due_at); @endphp
                <div class="flex flex-col md:flex-row md:items-center gap-3 px-5 py-4">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-signature text-purple-500 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('documents.show', $doc) }}" class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate block">{{ $doc->title }}</a>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-[10px] text-slate-400">
                                <span class="font-mono">{{ $doc->reference }}</span>
                                <span><i class="fa-solid fa-user mr-1"></i>Demandé par {{ $sr->requester?->full_name ?? '—' }} {{ $sr->created_at->diffForHumans() }}</span>
                                @if($badge)<span class="px-1.5 py-0.5 rounded font-bold {{ $badge[0] }}">{{ $badge[1] }}</span>@endif
                            </div>
                            @if($sr->message)
                            <p class="text-xs text-slate-500 italic mt-1.5 line-clamp-2">« {{ $sr->message }} »</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 md:pl-3">
                        <a href="{{ route('documents.signatures', $doc) }}"
                           class="inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-500 text-white text-[10px] font-black uppercase px-3 py-2 rounded-lg transition-all">
                            <i class="fa-solid fa-signature text-[9px]"></i> Signer
                        </a>
                        <button type="button"
                            @click="openModal(@js(route('documents.signature-requests.decline', [$doc, $sr])), @js('Refuser de signer « ' . $doc->title . ' »'), 'Motif du refus (envoyé au demandeur)')"
                            class="inline-flex items-center gap-1.5 bg-white border border-red-200 hover:bg-red-50 text-red-600 text-[10px] font-black uppercase px-3 py-2 rounded-lg transition-all">
                            <i class="fa-solid fa-xmark text-[9px]"></i> Refuser
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- Documents à corriger --}}
        @if($toFix->isNotEmpty())
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Mes documents à corriger ({{ $toFix->count() }})</h2>
            </div>
            <div class="divide-y divide-slate-50">
                @foreach($toFix as $doc)
                <div class="flex flex-col md:flex-row md:items-center gap-3 px-5 py-4">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-rotate-left text-red-500 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('documents.show', $doc) }}" class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate block">{{ $doc->title }}</a>
                            <p class="text-[10px] text-slate-400 mt-1">
                                Rejeté par <span class="font-bold text-slate-500">{{ $doc->rejection?->approver?->full_name ?? '—' }}</span>
                                {{ $doc->rejection?->decided_at?->diffForHumans() }}
                            </p>
                            @if($doc->rejection?->comment)
                            <p class="text-xs text-red-600/80 bg-red-50/50 rounded-lg px-2.5 py-1.5 mt-1.5">« {{ $doc->rejection->comment }} »</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 md:pl-3">
                        <a href="{{ route('documents.edit', $doc) }}"
                           class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-black uppercase px-3 py-2 rounded-lg transition-all">
                            <i class="fa-solid fa-pen text-[9px]"></i> Modifier
                        </a>
                        <a href="{{ route('documents.approval', $doc) }}"
                           class="inline-flex items-center gap-1.5 bg-orange-600 hover:bg-orange-500 text-white text-[10px] font-black uppercase px-3 py-2 rounded-lg transition-all">
                            <i class="fa-solid fa-paper-plane text-[9px]"></i> Resoumettre
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif
    </div>

    {{-- ===================== PARTAGÉS AVEC MOI ===================== --}}
    <div x-show="tab === 'shared'" x-cloak>
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-2 px-5 py-4 border-b border-slate-50">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Reçus ces {{ \App\Services\InboxService::RECENT_SHARE_DAYS }} derniers jours</h2>
            </div>
            @forelse($shares as $share)
            @php $doc = $share->document; @endphp
            <a href="{{ route('documents.show', $doc) }}" class="flex items-start gap-3 px-5 py-4 border-b border-slate-50 last:border-0 hover:bg-slate-50/60 transition-colors">
                <div class="w-9 h-9 rounded-xl {{ $share->accessed_at ? 'bg-slate-50' : 'bg-orange-50' }} flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-share-nodes {{ $share->accessed_at ? 'text-slate-400' : 'text-orange-500' }} text-sm"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="text-sm {{ $share->accessed_at ? 'font-semibold text-slate-600' : 'font-black text-slate-900' }} truncate">{{ $doc->title }}</p>
                        @unless($share->accessed_at)
                        <span class="bg-orange-500 text-white text-[8px] font-black uppercase rounded px-1.5 py-0.5 shrink-0">Nouveau</span>
                        @endunless
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-[10px] text-slate-400">
                        <span><i class="fa-solid fa-user mr-1"></i>{{ $share->sharedBy?->full_name ?? '—' }}</span>
                        <span>{{ $share->created_at->diffForHumans() }}</span>
                        <span class="px-1.5 py-0.5 rounded font-bold {{ $share->access_level === 'edit' ? 'bg-orange-50 text-orange-600' : 'bg-slate-50 text-slate-500' }}">
                            {{ $share->access_level === 'edit' ? 'Modification' : 'Consultation' }}
                        </span>
                        @if($share->expires_at)<span>Expire le {{ $share->expires_at->format('d/m/Y') }}</span>@endif
                    </div>
                    @if($share->message)
                    <p class="text-xs text-slate-500 italic mt-1.5 line-clamp-2">« {{ $share->message }} »</p>
                    @endif
                </div>
            </a>
            @empty
            <div class="flex flex-col items-center justify-center py-14 text-center">
                <i class="fa-solid fa-share-nodes text-slate-200 text-3xl mb-3"></i>
                <p class="text-xs font-bold text-slate-400">Aucun document partagé avec vous récemment</p>
            </div>
            @endforelse
        </section>
    </div>

    {{-- ===================== SUIVI ===================== --}}
    <div x-show="tab === 'follow'" x-cloak class="space-y-5">
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Mes documents en cours d'approbation</h2>
            </div>
            @forelse($myApprovals as $doc)
            @php $current = $doc->approvalSteps->firstWhere('status', 'pending'); @endphp
            <div class="px-5 py-4 border-b border-slate-50 last:border-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <a href="{{ route('documents.approval', $doc) }}" class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate">{{ $doc->title }}</a>
                    @if($current)
                    <span class="text-[10px] text-slate-400">
                        En attente de <span class="font-bold text-slate-600">{{ $current->approver?->full_name ?? '—' }}</span>
                        @if($current->due_at?->isPast())<span class="text-red-600 font-bold">· en retard</span>@endif
                    </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                    @foreach($doc->approvalSteps as $step)
                    @php
                        $style = match($step->status) {
                            'approved' => ['bg-emerald-50 text-emerald-600', 'fa-check'],
                            'rejected' => ['bg-red-50 text-red-600', 'fa-xmark'],
                            'skipped'  => ['bg-slate-50 text-slate-300', 'fa-minus'],
                            default    => $step->is($current) ? ['bg-blue-50 text-blue-600 ring-1 ring-blue-200', 'fa-hourglass-half'] : ['bg-slate-50 text-slate-400', 'fa-clock'],
                        };
                    @endphp
                    @if(!$loop->first)<i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>@endif
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold {{ $style[0] }}">
                        <i class="fa-solid {{ $style[1] }} text-[8px]"></i> {{ $step->approver?->full_name ?? '—' }}
                    </span>
                    @endforeach
                </div>
            </div>
            @empty
            <p class="text-xs text-slate-300 text-center py-10">Aucun document en cours d'approbation</p>
            @endforelse
        </section>

        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Signatures que j'ai demandées</h2>
            </div>
            @forelse($mySignatureRequests as $sr)
            @php
                $statusStyle = [
                    'pending'   => 'bg-amber-50 text-amber-600',
                    'signed'    => 'bg-emerald-50 text-emerald-600',
                    'declined'  => 'bg-red-50 text-red-600',
                    'cancelled' => 'bg-slate-100 text-slate-400',
                ][$sr->status] ?? 'bg-slate-100 text-slate-500';
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 px-5 py-3 border-b border-slate-50 last:border-0">
                <div class="min-w-0 flex-1">
                    <a href="{{ route('documents.signatures', $sr->document) }}" class="text-sm font-bold text-slate-800 hover:text-orange-600 truncate block">{{ $sr->document->title }}</a>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        {{ $sr->signer?->full_name ?? '—' }} · demandé {{ $sr->created_at->diffForHumans() }}
                        @if($sr->isOverdue())<span class="text-red-600 font-bold">· en retard</span>@endif
                    </p>
                    @if($sr->status === 'declined' && $sr->decline_reason)
                    <p class="text-xs text-red-600/80 mt-1">« {{ $sr->decline_reason }} »</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider {{ $statusStyle }}">
                        {{ \App\Models\SignatureRequest::STATUSES[$sr->status] ?? $sr->status }}
                    </span>
                    @if($sr->isPending())
                    <form method="POST" action="{{ route('documents.signature-requests.cancel', [$sr->document, $sr]) }}"
                          onsubmit="return confirm('Annuler cette demande de signature ?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-[10px] font-bold text-slate-400 hover:text-red-600 transition-colors">Annuler</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p class="text-xs text-slate-300 text-center py-10">Aucune demande de signature récente</p>
            @endforelse
        </section>
    </div>

    {{-- MODALE MOTIF (rejet / refus) --}}
    <div x-show="modal.open" x-cloak class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
         @keydown.escape.window="modal.open = false">
        <div @click="modal.open = false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <form method="POST" :action="modal.action" @click.stop
              class="relative bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl p-6 space-y-4">
            @csrf
            <h2 class="text-sm font-black text-slate-900" x-text="modal.title"></h2>
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5" x-text="modal.label"></label>
                <textarea :name="modal.field" rows="3" required maxlength="1000" x-ref="reason"
                          x-effect="modal.open && $nextTick(() => $refs.reason.focus())"
                          class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="modal.open = false"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">Annuler</button>
                <button type="submit"
                    class="flex-1 bg-red-600 hover:bg-red-500 text-white py-2.5 rounded-xl font-black text-xs uppercase tracking-widest transition-all active:scale-95">Confirmer</button>
            </div>
        </form>
    </div>
</div>
@endsection
