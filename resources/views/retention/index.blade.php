@extends('layouts.app')

@section('content')
@php
    $dispositionBadge = [
        'destroy' => ['Détruire', 'bg-red-50 text-red-600'],
        'review'  => ['Réexaminer', 'bg-amber-50 text-amber-700'],
        'keep'    => ['Conserver', 'bg-emerald-50 text-emerald-700'],
    ];
    $tabs = [
        'due'      => ['À décider', 'fa-hourglass-end'],
        'upcoming' => ['Dans les 90 jours', 'fa-hourglass-half'],
        'records'  => ['Procès-verbaux', 'fa-file-signature'],
        'integrity' => ['Intégrité & export', 'fa-shield-halved'],
    ];
@endphp
<div class="space-y-5" x-data="{ selected: [], modal: null,
        get allIds() { return @js($documents ? $documents->getCollection()->pluck('id') : []) },
        toggleAll(on) { this.selected = on ? [...this.allIds] : [] } }">

    {{-- EN-TÊTE --}}
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Fin de conservation</h1>
        <p class="text-xs text-slate-400 font-medium mt-1.5 max-w-3xl">
            Les documents arrivés au terme de leur durée de conservation attendent votre décision. Rien n'est jamais détruit automatiquement :
            chaque élimination est validée par un administrateur et donne lieu à un procès-verbal conservé définitivement.
        </p>
    </div>

    {{-- ONGLETS --}}
    <div class="flex flex-wrap gap-2">
        @foreach($tabs as $key => [$label, $icon])
        <a href="{{ route('retention.index', ['tab' => $key]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all
                  {{ $tab === $key ? 'bg-slate-900 text-white shadow-md' : 'bg-white border border-slate-100 text-slate-500 hover:text-slate-800 shadow-sm' }}">
            <i class="fa-solid {{ $icon }} text-[10px]"></i> {{ $label }}
            @isset($counts[$key])
            <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-black flex items-center justify-center
                         {{ $tab === $key ? 'bg-white/15' : ($key === 'due' && $counts['due'] ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500') }}">{{ $counts[$key] }}</span>
            @endisset
        </a>
        @endforeach
    </div>

    @if($errors->any())
    <div class="flex items-start gap-3 bg-red-50 border border-red-100 rounded-2xl px-4 py-3 text-xs text-red-700">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <div>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    </div>
    @endif

    @if($tab === 'integrity')
    {{-- ================= INTÉGRITÉ & EXPORT ================= --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 items-start">
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-5 pt-5 pb-4 border-b border-slate-50 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Contrôle d'intégrité</h2>
                    <p class="text-[11px] text-slate-400 mt-1">Chaque dimanche, tous les fichiers sont relus et comparés à leur empreinte, et la chaîne du journal d'audit est vérifiée.</p>
                </div>
                <form method="POST" action="{{ route('retention.verify') }}">@csrf
                    <button class="shrink-0 px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold"><i class="fa-solid fa-shield-halved mr-1"></i> Vérifier maintenant</button>
                </form>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($checks as $check)
                <div class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 {{ $check->passed() ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                            <i class="fa-solid {{ $check->passed() ? 'fa-circle-check' : 'fa-triangle-exclamation' }} text-xs"></i>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-800">{{ $check->passed() ? 'Tout est intact' : 'Anomalies détectées' }}
                                <span class="font-medium text-slate-400">· {{ $check->created_at->format('d/m/Y H:i') }}</span></p>
                            <p class="text-[10px] text-slate-500">{{ $check->files_checked }} fichier(s) contrôlé(s) · {{ $check->files_failed }} en défaut · journal {{ $check->audit_chain_ok ? 'intact' : 'altéré' }}
                                @if($check->baselines) · {{ $check->baselines }} empreinte(s) enregistrée(s) @endif</p>
                        </div>
                    </div>
                    @if($check->problems)
                    <ul class="mt-2 ml-11 space-y-0.5 text-[10px] text-red-600">
                        @foreach(array_slice($check->problems, 0, 5) as $problem)<li>• {{ $problem }}</li>@endforeach
                        @if(count($check->problems) > 5)<li class="text-slate-400">… et {{ count($check->problems) - 5 }} autre(s)</li>@endif
                    </ul>
                    @endif
                </div>
                @empty
                <p class="px-5 py-10 text-center text-xs text-slate-400">Aucun contrôle pour l'instant. Lancez le premier avec « Vérifier maintenant ».</p>
                @endforelse
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm">
            <div class="px-5 pt-5 pb-4 border-b border-slate-50 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Export complet</h2>
                    <p class="text-[11px] text-slate-400 mt-1">Tous vos documents (par catégorie), leurs versions, leurs informations (CSV), le journal d'audit et les procès-verbaux, dans un ZIP. Disponible {{ config('ged.export_days') }} jours.</p>
                </div>
                <form method="POST" action="{{ route('retention.exports.store') }}">@csrf
                    <button class="shrink-0 px-3 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white text-[11px] font-bold"><i class="fa-solid fa-file-zipper mr-1"></i> Exporter</button>
                </form>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($exports as $export)
                <div class="px-5 py-3 flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center shrink-0"><i class="fa-solid fa-file-zipper text-xs"></i></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800">{{ $export->created_at->format('d/m/Y H:i') }}
                            <span class="font-medium text-slate-400">· {{ $export->requester?->full_name ?? '—' }}</span></p>
                        <p class="text-[10px] text-slate-500">
                            @switch($export->status)
                                @case('done') {{ $export->documents_count }} document(s) · {{ formatBytes($export->size) }} · {{ $export->isReady() ? 'expire le ' . $export->expires_at->format('d/m/Y') : 'expiré' }} @break
                                @case('failed') <span class="text-red-600">Échec : {{ Str::limit($export->error, 80) }}</span> @break
                                @default <i class="fa-solid fa-spinner fa-spin mr-1"></i> En préparation…
                            @endswitch
                        </p>
                    </div>
                    @if($export->isReady())
                    <a href="{{ route('retention.exports.download', $export) }}" data-no-loader class="px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-[11px] font-bold text-slate-600"><i class="fa-solid fa-download mr-1"></i> Télécharger</a>
                    @endif
                </div>
                @empty
                <p class="px-5 py-10 text-center text-xs text-slate-400">Aucun export.</p>
                @endforelse
            </div>
        </section>
    </div>

    @elseif($tab === 'records')
    {{-- ================= PROCÈS-VERBAUX ================= --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @forelse($records as $record)
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-b border-slate-50 last:border-0">
            <span class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0"><i class="fa-solid fa-file-signature text-sm"></i></span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-black text-slate-900">{{ $record->number }}
                    <span class="text-xs font-bold text-slate-400">· {{ $record->documents_count }} document(s)</span></p>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $record->created_at->format('d/m/Y à H:i') }} · validé par {{ $record->approved_by_name }}</p>
                <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ $record->reason }}</p>
            </div>
            @if($record->pdf_path)
            <a href="{{ route('retention.records.pdf', $record) }}" data-no-loader
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 text-xs font-bold text-slate-600 self-start sm:self-auto">
                <i class="fa-solid fa-file-pdf text-red-500"></i> Télécharger le PV
            </a>
            @endif
        </div>
        @empty
        <div class="text-center py-16 px-6">
            <i class="fa-solid fa-file-signature text-slate-200 text-3xl"></i>
            <p class="text-sm font-bold text-slate-500 mt-3">Aucune élimination pour l'instant</p>
            <p class="text-xs text-slate-400 mt-1">Chaque élimination validée produit ici un procès-verbal en PDF.</p>
        </div>
        @endforelse
    </div>
    {{ $records->links() }}

    @else
    {{-- ================= DOCUMENTS ================= --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if($documents->isEmpty())
        <div class="text-center py-16 px-6">
            <i class="fa-solid fa-circle-check text-emerald-300 text-3xl"></i>
            <p class="text-sm font-bold text-slate-600 mt-3">{{ $tab === 'due' ? 'Aucun document en fin de conservation' : 'Aucune fin de conservation dans les 90 prochains jours' }}</p>
            <p class="text-xs text-slate-400 mt-1">La durée de conservation se règle par catégorie (Catégories → Modifier).</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    <tr>
                        <th class="px-4 py-3 w-10"><input type="checkbox" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                                   @change="toggleAll($event.target.checked)" :checked="selected.length && selected.length === allIds.length"></th>
                        <th class="px-2 py-3">Document</th>
                        <th class="px-2 py-3">Catégorie</th>
                        <th class="px-2 py-3">Fin de conservation</th>
                        <th class="px-2 py-3">Sort final</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($documents as $document)
                    @php [$label, $class] = $dispositionBadge[$document->finalDisposition()] ?? $dispositionBadge['review']; @endphp
                    <tr class="hover:bg-slate-50/50" :class="selected.includes({{ $document->id }}) ? 'bg-orange-50/40' : ''">
                        <td class="px-4 py-3"><input type="checkbox" value="{{ $document->id }}" x-model.number="selected" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500"></td>
                        <td class="px-2 py-3 min-w-[14rem]">
                            <a href="{{ route('documents.show', $document) }}" class="text-xs font-bold text-slate-800 hover:text-orange-600">{{ $document->title }}</a>
                            <p class="text-[10px] font-mono text-slate-400">{{ $document->reference }} · déposé le {{ $document->created_at->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-2 py-3 text-xs text-slate-600">{{ $document->category?->name ?? '—' }}</td>
                        <td class="px-2 py-3 whitespace-nowrap">
                            <p class="text-xs font-bold {{ $document->retention_until->isPast() ? 'text-red-600' : 'text-slate-700' }}">{{ $document->retention_until->format('d/m/Y') }}</p>
                            <p class="text-[10px] text-slate-400">{{ $document->retention_years }} an(s) · {{ $document->retention_until->locale('fr')->diffForHumans() }}</p>
                        </td>
                        <td class="px-2 py-3"><span class="inline-block px-2 py-1 rounded-lg text-[10px] font-bold {{ $class }}">{{ $label }}</span></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($document->isUnderLegalHold())
                            <span class="text-[9px] font-black uppercase tracking-wider text-orange-700 bg-orange-50 rounded px-1.5 py-0.5" title="Ne peut pas être éliminé tant que le gel est actif">Gel juridique</span>
                            @endif
                            @if($document->isArchived())
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500 bg-slate-100 rounded px-1.5 py-0.5">Archivé</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    {{ $documents->links() }}

    {{-- Barre de décision --}}
    <div x-show="selected.length" x-cloak x-transition class="sticky bottom-4 z-30 sm:mr-32">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900 text-white rounded-2xl shadow-2xl px-4 py-3">
            <p class="text-xs font-bold"><span x-text="selected.length"></span> document(s) sélectionné(s)</p>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="modal = 'keep'" class="px-3 py-2 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/15"><i class="fa-solid fa-infinity mr-1"></i> Conserver définitivement</button>
                <button type="button" @click="modal = 'extend'" class="px-3 py-2 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/15"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Prolonger</button>
                @if($tab === 'due')
                <button type="button" @click="modal = 'eliminate'" class="px-3 py-2 rounded-xl text-xs font-black bg-red-600 hover:bg-red-500"><i class="fa-solid fa-trash-can mr-1"></i> Éliminer</button>
                @endif
            </div>
        </div>
    </div>

    {{-- Fenêtres de décision --}}
    @foreach([
        'eliminate' => [route('retention.eliminate'), 'Éliminer définitivement', 'bg-red-600 hover:bg-red-500', 'fa-trash-can'],
        'extend'    => [route('retention.extend'), 'Prolonger la conservation', 'bg-slate-900 hover:bg-slate-800', 'fa-clock-rotate-left'],
        'keep'      => [route('retention.keep'), 'Conserver définitivement', 'bg-emerald-600 hover:bg-emerald-500', 'fa-infinity'],
    ] as $key => [$action, $title, $button, $icon])
    <div x-show="modal === '{{ $key }}'" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" @keydown.escape.window="modal = null">
        <form method="POST" action="{{ $action }}" @click.outside="modal = null" class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 space-y-4">
            @csrf
            <template x-for="id in selected" :key="id"><input type="hidden" name="documents[]" :value="id"></template>
            <div>
                <h3 class="text-sm font-black text-slate-900"><i class="fa-solid {{ $icon }} mr-1"></i> {{ $title }} — <span x-text="selected.length"></span> document(s)</h3>
                @if($key === 'eliminate')
                <p class="text-xs text-slate-500 mt-1.5">Les fichiers et toutes leurs versions seront <b>détruits définitivement</b>. Un procès-verbal en PDF est produit avant toute suppression et conservé ; le journal d'audit des documents est gardé. Les documents sous gel juridique ou à conserver sont ignorés.</p>
                @elseif($key === 'extend')
                <p class="text-xs text-slate-500 mt-1.5">La durée de conservation de chaque document est allongée ; ils réapparaîtront ici à la nouvelle échéance.</p>
                @else
                <p class="text-xs text-slate-500 mt-1.5">Ces documents ne seront plus jamais proposés à l'élimination.</p>
                @endif
            </div>
            @if($key === 'extend')
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Prolonger de</label>
                <select name="years" class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    @foreach([1, 2, 3, 5, 10] as $years)<option value="{{ $years }}">{{ $years }} an(s)</option>@endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Motif <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="2" required minlength="{{ $key === 'eliminate' ? 10 : 5 }}" maxlength="1000"
                          placeholder="{{ $key === 'eliminate' ? 'Ex. : durée légale de conservation échue, aucun contentieux en cours' : ($key === 'extend' ? 'Ex. : contrôle fiscal annoncé' : 'Ex. : valeur historique') }}"
                          class="w-full bg-slate-50 border border-slate-100 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
            </div>
            @if($key === 'eliminate')
            <label class="flex items-start gap-2 text-xs text-slate-600 cursor-pointer">
                <input type="checkbox" name="confirm" value="1" required class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                Je confirme que cette destruction est définitive et autorisée.
            </label>
            @endif
            <div class="flex justify-end gap-2">
                <button type="button" @click="modal = null" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100">Annuler</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-white text-xs font-black {{ $button }}">{{ $title }}</button>
            </div>
        </form>
    </div>
    @endforeach
    @endif
</div>
@endsection
