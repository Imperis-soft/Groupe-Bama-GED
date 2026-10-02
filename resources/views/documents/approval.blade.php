@extends('layouts.app')

@section('content')
@php
    $totalSteps    = $steps->count();
    $approvedCount = $steps->where('status', 'approved')->count();
    $rejectedCount = $steps->where('status', 'rejected')->count();
    $pendingCount  = $steps->where('status', 'pending')->count();
    $progress      = $totalSteps > 0 ? round(($approvedCount / $totalSteps) * 100) : 0;
    $canConfigure  = auth()->user()->hasRole('admin') || $document->creator_id === auth()->id();
    // Un circuit ne se lance que sur un brouillon (contenu gelé ensuite jusqu'à la décision)
    $canLaunch     = $canConfigure && $document->status === 'draft' && !$document->isUnderLegalHold();
    $rule          = $document->workflowRule();
@endphp

<div x-data="{
    modal: {{ ($canLaunch && $steps->isEmpty()) ? 'true' : 'false' }},
    selected: [],
    dueDays: '',
    dues: {},
    templateId: null,
    templates: @js($resolvedTemplates),
    suggestedTemplateId: @js($suggestedTemplateId),
    toggleUser(id) {
        const idx = this.selected.indexOf(id);
        if (idx === -1) this.selected.push(id);
        else this.selected.splice(idx, 1);
    },
    // Un modèle pré-remplit les validateurs et leurs délais (toujours modifiables)
    applyTemplate(template) {
        this.templateId = template.id;
        this.selected = template.steps.map(s => s.user_id);
        this.dues = Object.fromEntries(template.steps.map(s => [s.user_id, s.due_days ?? '']));
    },
    isSelected(id) { return this.selected.includes(id); },
    moveUp(idx) { if (idx > 0) { [this.selected[idx], this.selected[idx-1]] = [this.selected[idx-1], this.selected[idx]]; this.selected = [...this.selected]; } },
    moveDown(idx) { if (idx < this.selected.length - 1) { [this.selected[idx], this.selected[idx+1]] = [this.selected[idx+1], this.selected[idx]]; this.selected = [...this.selected]; } },
    remove(idx) { this.selected.splice(idx, 1); this.selected = [...this.selected]; }
}" class="space-y-6">

{{-- ── BREADCRUMB ── --}}
<nav class="flex items-center gap-2 text-[11px] text-slate-400 font-semibold overflow-hidden">
    <a href="{{ route('dashboard') }}" class="hover:text-orange-500 transition-colors shrink-0"><i class="fa-solid fa-house text-[10px]"></i></a>
    <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 shrink-0"></i>
    <a href="{{ route('documents.index') }}" class="hover:text-orange-500 transition-colors shrink-0">Documents</a>
    <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 shrink-0"></i>
    <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-500 transition-colors truncate">{{ $document->title }}</a>
    <i class="fa-solid fa-chevron-right text-[8px] text-slate-300 shrink-0"></i>
    <span class="text-slate-600 shrink-0">Approbation</span>
</nav>

{{-- ── HERO ── --}}
<div class="relative bg-slate-900 rounded-3xl overflow-hidden shadow-xl">
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-20 -right-20 w-72 h-72 bg-orange-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-12 -left-12 w-56 h-56 bg-blue-600/10 rounded-full blur-2xl"></div>
    </div>
    <div class="relative px-6 py-6 sm:px-8 sm:py-7">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-orange-600 flex items-center justify-center shrink-0 shadow-lg shadow-orange-900/40">
                    <i class="fa-solid fa-list-check text-white text-lg"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-0.5">Validation</p>
                    <h1 class="text-xl sm:text-2xl font-black text-white leading-tight">{{ $document->title }}</h1>
                    <p class="text-[11px] text-slate-400 mt-0.5 font-mono">{{ $document->reference }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($canLaunch)
                <button @click="modal = true"
                    class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black px-4 py-2.5 rounded-xl shadow-lg shadow-orange-900/30 transition-all">
                    <i class="fa-solid fa-{{ $steps->isEmpty() ? 'rocket' : 'rotate' }} text-[10px]"></i>
                    {{ $steps->isEmpty() ? 'Démarrer' : 'Nouveau circuit' }}
                </button>
                @endif
                <a href="{{ route('documents.show', $document) }}"
                   class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/10 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
                </a>
            </div>
        </div>

        {{-- Stats + progress --}}
        <div class="mt-6 grid grid-cols-3 gap-3 sm:gap-4">
            <div class="bg-white/5 rounded-2xl px-4 py-3 text-center">
                <p class="text-2xl font-black text-white">{{ $totalSteps }}</p>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Total</p>
            </div>
            <div class="bg-emerald-500/10 rounded-2xl px-4 py-3 text-center">
                <p class="text-2xl font-black text-emerald-400">{{ $approvedCount }}</p>
                <p class="text-[9px] font-bold text-emerald-500/70 uppercase tracking-widest mt-0.5">Validés</p>
            </div>
            <div class="bg-amber-500/10 rounded-2xl px-4 py-3 text-center">
                <p class="text-2xl font-black text-amber-400">{{ $pendingCount }}</p>
                <p class="text-[9px] font-bold text-amber-500/70 uppercase tracking-widest mt-0.5">En attente</p>
            </div>
        </div>

        @if($totalSteps > 0)
        <div class="mt-4">
            <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700
                    {{ $rejectedCount > 0 ? 'bg-red-500' : ($progress === 100 ? 'bg-emerald-500' : 'bg-orange-500') }}"
                     style="width: {{ $progress }}%"></div>
            </div>
            <p class="text-[9px] text-slate-500 mt-1.5 text-right font-bold">{{ $progress }}% complété</p>
        </div>
        @endif
    </div>
</div>

{{-- ── ALERTES ── --}}
@if(session('success'))
<div class="flex items-start gap-3 bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4">
    <i class="fa-solid fa-circle-check text-emerald-500 text-lg shrink-0 mt-0.5"></i>
    <div>
        <p class="text-sm font-black text-emerald-800">{{ session('success') }}</p>
        @if($canLaunch && $steps->where('status', 'pending')->isEmpty())
        <p class="text-xs text-emerald-600 mt-0.5">
            Cliquez sur <strong>Démarrer</strong> pour configurer les validateurs
            @unless($rule['approval'])
            ou <a href="{{ route('documents.show', $document) }}" class="underline font-bold">ignorez cette étape</a>
            @endunless
        </p>
        @endif
    </div>
</div>
@endif

@include('documents._workflow-banner')

{{-- ── ÉTAPES ── --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-50">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center">
                <i class="fa-solid fa-diagram-next text-blue-500 text-xs"></i>
            </div>
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Étapes de validation</h2>
        </div>
        @if($totalSteps > 0)
        <span class="text-[9px] font-black px-2.5 py-1 rounded-lg
            {{ $progress === 100 ? 'bg-emerald-50 text-emerald-600' : ($rejectedCount > 0 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600') }}">
            {{ $progress === 100 ? '✓ Terminé' : ($rejectedCount > 0 ? '✗ Rejeté' : '⏳ En cours') }}
        </span>
        @endif
    </div>

    @if($steps->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 text-center px-6">
        <div class="w-16 h-16 rounded-3xl bg-slate-50 flex items-center justify-center mb-4">
            <i class="fa-solid fa-list-check text-slate-200 text-2xl"></i>
        </div>
        <p class="text-sm font-black text-slate-400">Pas encore de validateurs</p>
        <p class="text-xs text-slate-300 mt-1 max-w-xs">Cliquez sur <strong class="text-slate-400">Démarrer</strong> pour choisir qui doit valider ce document.</p>
        @if($canLaunch)
        <button @click="modal = true"
            class="mt-5 inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all">
            <i class="fa-solid fa-rocket text-[10px]"></i> Démarrer le workflow
        </button>
        @endif
    </div>
    @else
    <div class="divide-y divide-slate-50">
        @foreach($steps as $step)
        <div class="relative px-5 py-5" x-data="{ showReject: false }">
            @if(!$loop->last)
            <div class="absolute left-[2.4rem] top-[4rem] bottom-0 w-px bg-slate-100 z-0"></div>
            @endif
            <div class="flex items-start gap-4 relative z-10">
                {{-- Bulle statut --}}
                <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 font-black text-sm shadow-sm
                    {{ $step->status === 'approved' ? 'bg-emerald-500 text-white shadow-emerald-200' :
                       ($step->status === 'rejected' ? 'bg-red-500 text-white shadow-red-200' :
                       'bg-white border-2 border-slate-200 text-slate-400') }}">
                    @if($step->status === 'approved') <i class="fa-solid fa-check text-xs"></i>
                    @elseif($step->status === 'rejected') <i class="fa-solid fa-xmark text-xs"></i>
                    @else {{ $step->step_order }}
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3 flex-wrap">
                        <div>
                            <p class="text-sm font-black text-slate-800">
                                {{ $step->approver->full_name }}
                                @if($currentStep?->id === $step->id)
                                <span class="ml-1 text-[9px] font-black uppercase tracking-wider text-blue-600 bg-blue-50 rounded px-1.5 py-0.5 align-middle">En cours</span>
                                @endif
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                <i class="fa-solid fa-envelope text-[8px] mr-1"></i>{{ $step->approver->email }}
                            </p>
                            @if($step->delegatedFrom)
                            <p class="text-[10px] text-purple-600 font-bold mt-0.5">
                                <i class="fa-solid fa-user-clock text-[9px] mr-1"></i>En remplacement de {{ $step->delegatedFrom->full_name }} (absent·e)
                            </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 shrink-0 flex-wrap">
                            @if($step->isOverdue())
                            <span class="text-[9px] font-black text-red-600 bg-red-50 ring-1 ring-red-200 px-2 py-1 rounded-lg">
                                <i class="fa-solid fa-triangle-exclamation text-[8px] mr-0.5"></i>En retard · {{ $step->due_at->format('d/m/Y') }}
                            </span>
                            @elseif($step->due_at)
                            <span class="text-[9px] font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">
                                <i class="fa-regular fa-clock text-[8px] mr-0.5"></i>Avant le {{ $step->due_at->format('d/m/Y') }}
                            </span>
                            @elseif($step->isPending() && $step->due_days)
                            <span class="text-[9px] font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded-lg" title="Le délai commencera quand l'étape deviendra active">
                                <i class="fa-regular fa-clock text-[8px] mr-0.5"></i>{{ $step->due_days }} j
                            </span>
                            @endif
                            @if($step->reminders_sent)
                            <span class="text-[9px] font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-lg" title="Dernière relance : {{ $step->last_reminded_at?->format('d/m/Y H:i') }}">
                                <i class="fa-solid fa-bell text-[8px] mr-0.5"></i>{{ $step->reminders_sent }} relance(s)
                            </span>
                            @endif
                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider
                                {{ $step->status === 'approved' ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200' :
                                   ($step->status === 'rejected' ? 'bg-red-50 text-red-600 ring-1 ring-red-200' :
                                   'bg-amber-50 text-amber-600 ring-1 ring-amber-200') }}">
                                {{ match($step->status) { 'approved' => 'Validé', 'rejected' => 'Rejeté', 'skipped' => 'Annulé', default => 'En attente' } }}
                            </span>
                        </div>
                    </div>

                    @if($step->comment)
                    <div class="mt-2 flex items-start gap-2 bg-slate-50 rounded-xl px-3 py-2.5">
                        <i class="fa-solid fa-quote-left text-slate-300 text-[10px] mt-0.5 shrink-0"></i>
                        <p class="text-xs text-slate-600 italic leading-relaxed">{{ $step->comment }}</p>
                    </div>
                    @endif

                    @if($step->decided_at)
                    <p class="text-[9px] text-slate-400 mt-1.5">
                        <i class="fa-regular fa-calendar-check text-[8px] mr-1"></i>
                        Le {{ $step->decided_at->format('d/m/Y à H:i') }}
                        @if($step->document_version)
                        · sur la version {{ $step->document_version }}
                        <span class="font-mono" title="Empreinte SHA-256 du fichier décidé : {{ $step->document_checksum }}">({{ \Illuminate\Support\Str::limit($step->document_checksum, 10, '…') }})</span>
                        @endif
                    </p>
                    @endif
                    @if($step->forcedBy)
                    <div class="mt-2 flex items-start gap-2 bg-red-50 ring-1 ring-red-100 rounded-xl px-3 py-2.5">
                        <i class="fa-solid fa-user-shield text-red-400 text-[10px] mt-0.5 shrink-0"></i>
                        <p class="text-xs text-red-700"><strong>Décidé par {{ $step->forcedBy->full_name }}</strong> (administrateur) à la place de l'approbateur — motif : {{ $step->force_reason }}</p>
                    </div>
                    @endif

                    @if($currentStep?->id === $step->id && $canConfigure && $step->approver_id !== auth()->id())
                    <form action="{{ route('documents.approval.remind', [$document, $step]) }}" method="POST" class="mt-2">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-lg transition-all">
                            <i class="fa-solid fa-bell text-[9px]"></i> Relancer {{ \Illuminate\Support\Str::before($step->approver->full_name, ' ') }}
                        </button>
                    </form>
                    @endif

                    @php
                        $isApprover = $step->approver_id === auth()->id() || $step->delegated_from_id === auth()->id();
                        $forcing = !$isApprover && auth()->user()->hasRole('admin') && $document->creator_id !== auth()->id();
                    @endphp
                    @if($currentStep?->id === $step->id && $document->status === 'review' && ($isApprover || $forcing))
                    <div class="mt-3 space-y-2" @if($forcing) x-data="{ forceReason: '' }" @endif>
                        @if($forcing)
                        <div class="bg-red-50 ring-1 ring-red-100 rounded-xl px-3 py-2.5">
                            <p class="text-[10px] font-bold text-red-700 mb-1.5"><i class="fa-solid fa-user-shield mr-1"></i>Vous n'êtes pas l'approbateur : votre décision sera inscrite au journal comme un forçage administrateur, et {{ $step->approver->full_name }} sera prévenu·e.</p>
                            <input type="text" x-model="forceReason" placeholder="Motif du forçage (obligatoire)"
                                   class="w-full bg-white border border-red-200 rounded-lg px-3 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        @endif
                        <div class="flex gap-2 flex-wrap">
                            <form action="{{ route('documents.approval.approve', [$document, $step]) }}" method="POST">
                                @csrf
                                @if($forcing)<input type="hidden" name="force_reason" :value="forceReason">@endif
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-md shadow-emerald-200 transition-all">
                                    <i class="fa-solid fa-check text-[9px]"></i> Valider
                                </button>
                            </form>
                            <button @click="showReject = !showReject"
                                class="inline-flex items-center gap-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all">
                                <i class="fa-solid fa-xmark text-[9px]"></i> Refuser
                            </button>
                        </div>
                        <div x-show="showReject" x-cloak x-transition>
                            <form action="{{ route('documents.approval.reject', [$document, $step]) }}" method="POST" class="flex gap-2">
                                @csrf
                                @if($forcing)<input type="hidden" name="force_reason" :value="forceReason">@endif
                                <input type="text" name="reason" required placeholder="Raison du refus..."
                                       class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 placeholder-slate-300 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all">
                                <button type="submit"
                                    class="bg-red-600 hover:bg-red-500 text-white text-[10px] font-black px-4 py-2 rounded-xl shadow-md shadow-red-200 transition-all active:scale-95">
                                    OK
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ── MODAL CONFIGURATION ── --}}
@if($canLaunch)
<div x-show="modal" x-cloak
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modal = false"></div>

    {{-- Panel --}}
    <div @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="relative bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[92vh] flex flex-col overflow-hidden">

        {{-- Header modal --}}
        <div class="bg-slate-900 px-6 py-5 shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-orange-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-list-check text-white text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white">Choisir les validateurs</h3>
                        <p class="text-[10px] text-slate-400 mt-0.5 truncate max-w-[220px]">{{ $document->title }}</p>
                    </div>
                </div>
                <button @click="modal = false"
                    class="w-8 h-8 flex items-center justify-center rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <form action="{{ route('documents.approval.setup', $document) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf

            <input type="hidden" name="template_id" :value="templateId">
            <div class="flex-1 overflow-y-auto">

                {{-- Modèles de circuit --}}
                <div x-show="templates.length" class="px-6 pt-5">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Partir d'un circuit</p>
                    <div class="space-y-1.5">
                        <template x-for="template in templates" :key="template.id">
                            <button type="button" @click="template.steps.length && applyTemplate(template)"
                                    :class="templateId === template.id ? 'border-orange-300 bg-orange-50' : 'border-slate-100 hover:border-orange-200 bg-white'"
                                    class="w-full text-left border rounded-xl px-3 py-2.5 transition-all">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-diagram-next text-orange-400 text-xs"></i>
                                    <span class="text-xs font-black text-slate-800 flex-1" x-text="template.name"></span>
                                    <span x-show="template.id === suggestedTemplateId" class="text-[8px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 rounded px-1.5 py-0.5">Suggéré</span>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-1">
                                    <template x-for="(step, i) in template.steps" :key="i">
                                        <span><span x-show="i > 0"> → </span><span x-text="step.name"></span><span x-show="step.due_days" class="text-slate-400" x-text="' (' + step.due_days + ' j)'"></span></span>
                                    </template>
                                </p>
                                <template x-for="error in template.errors" :key="error">
                                    <p class="text-[10px] text-red-600 mt-0.5"><i class="fa-solid fa-circle-exclamation mr-1"></i><span x-text="error"></span></p>
                                </template>
                            </button>
                        </template>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">Ou composez le circuit vous-même ci-dessous.</p>
                </div>

                {{-- Ordre sélectionné --}}
                <div x-show="selected.length > 0" class="px-6 pt-5 pb-3">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Ordre de validation</p>
                    <div class="space-y-1.5">
                        <template x-for="(id, idx) in selected" :key="id">
                            <div class="flex items-center gap-2 bg-orange-50 border border-orange-100 rounded-xl px-3 py-2">
                                <span class="w-5 h-5 rounded-lg bg-orange-600 text-white text-[9px] font-black flex items-center justify-center shrink-0" x-text="idx + 1"></span>
                                <span class="flex-1 text-xs font-bold text-slate-700 truncate"
                                      x-text="{{ json_encode($users->pluck('full_name', 'id')) }}[id]"></span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" @click="moveUp(idx)"
                                        :disabled="idx === 0"
                                        class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-700 disabled:opacity-30 transition-all">
                                        <i class="fa-solid fa-chevron-up text-[9px]"></i>
                                    </button>
                                    <button type="button" @click="moveDown(idx)"
                                        :disabled="idx === selected.length - 1"
                                        class="w-6 h-6 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-700 disabled:opacity-30 transition-all">
                                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                                    </button>
                                    <button type="button" @click="remove(idx)"
                                        class="w-6 h-6 flex items-center justify-center rounded-lg text-red-300 hover:bg-red-50 hover:text-red-500 transition-all">
                                        <i class="fa-solid fa-xmark text-[9px]"></i>
                                    </button>
                                </div>
                                <div class="relative w-16 shrink-0" title="Délai de cette étape">
                                    <input type="number" min="1" max="365" name="step_due_days[]" x-model="dues[id]" :placeholder="dueDays || '—'"
                                           class="w-full bg-white border border-orange-100 rounded-lg pl-2 pr-5 py-1 text-[11px] font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                    <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[9px] font-bold text-slate-400">j</span>
                                </div>
                                {{-- Hidden input pour le form --}}
                                <input type="hidden" name="approvers[]" :value="id">
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Séparateur --}}
                <div x-show="selected.length > 0" class="px-6">
                    <div class="border-t border-slate-100"></div>
                </div>

                {{-- Liste utilisateurs --}}
                <div class="px-6 pt-4 pb-3">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">
                        Ajouter un validateur
                    </p>
                    <div class="space-y-1">
                        @foreach($users as $user)
                        <button type="button" @click="toggleUser({{ $user->id }})"
                            :class="isSelected({{ $user->id }})
                                ? 'bg-orange-50 border-orange-200 opacity-50 cursor-default'
                                : 'bg-white border-slate-100 hover:border-orange-200 hover:bg-orange-50/40'"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border transition-all text-left">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 font-black text-xs text-slate-500">
                                {{ strtoupper(substr($user->full_name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-slate-800 truncate">{{ $user->full_name }}</p>
                                <p class="text-[9px] text-slate-400 truncate">{{ $user->email }}</p>
                                @if($user->isAbsent())
                                <p class="text-[9px] font-bold text-purple-600 truncate">
                                    Absent·e jusqu'au {{ $user->absent_until->format('d/m') }}{{ $user->delegate ? ' — remplacé·e par ' . $user->delegate->full_name : ' — aucun suppléant' }}
                                </p>
                                @endif
                            </div>
                            <div :class="isSelected({{ $user->id }}) ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-300'"
                                 class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 transition-all">
                                <i class="fa-solid fa-check text-[8px]"></i>
                            </div>
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Délai --}}
                <div class="px-6 pb-5">
                    <div class="border-t border-slate-100 pt-4">
                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                            Délai par défaut de chaque étape <span class="text-slate-300 font-normal normal-case">(optionnel, compté à partir de l'activation de l'étape)</span>
                        </label>
                        <div class="relative">
                            <input type="number" name="due_days" x-model="dueDays" min="1" max="365" placeholder="Ex: 7"
                                   class="w-full bg-slate-50 border border-slate-100 rounded-xl pl-4 pr-14 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">jours</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer modal --}}
            <div class="shrink-0 px-6 py-4 border-t border-slate-100 bg-white space-y-3">
                <div class="flex items-center gap-3">
                    <button type="button" @click="modal = false"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-xs uppercase tracking-wider transition-all">
                        Annuler
                    </button>
                    <button type="submit" :disabled="selected.length === 0"
                        :class="selected.length > 0 ? 'bg-orange-600 hover:bg-orange-500 shadow-lg shadow-orange-200 active:scale-95' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                        class="flex-1 inline-flex items-center justify-center gap-2 text-white py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all">
                        <i class="fa-solid fa-paper-plane text-[10px]"></i>
                        Lancer
                        <span x-show="selected.length > 0" class="bg-white/20 text-[9px] font-black px-1.5 py-0.5 rounded-md" x-text="selected.length + ' validateur' + (selected.length > 1 ? 's' : '')"></span>
                    </button>
                </div>
                @if($rule['approval'])
                <p class="text-center text-[10px] text-red-600 font-bold py-0.5">
                    <i class="fa-solid fa-lock text-[9px] mr-1"></i> Approbation obligatoire pour cette catégorie
                </p>
                @else
                <a href="{{ route('documents.show', $document) }}"
                   class="block text-center text-[10px] text-slate-400 hover:text-slate-600 font-bold transition-colors py-0.5">
                    <i class="fa-solid fa-forward-step text-[9px] mr-1"></i> Ignorer pour l'instant
                </a>
                @endif
            </div>
        </form>
    </div>
</div>
@endif

</div>
@endsection
