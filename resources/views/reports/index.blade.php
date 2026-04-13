@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Rapports</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Analyse et conformité documentaire</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap self-start sm:self-auto">
            <a href="{{ route('reports.export-csv') }}"
               class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white text-xs font-black uppercase tracking-widest px-4 py-2.5 rounded-xl shadow-lg shadow-green-200 transition-all active:scale-95">
                <i class="fa-solid fa-file-csv text-[10px]"></i> Export Documents
            </a>
            <a href="{{ route('reports.export-audit') }}"
               class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all">
                <i class="fa-solid fa-file-csv text-[10px]"></i> Export Audit
            </a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach([
            ['label' => 'Total',        'value' => $stats['total'],        'color' => 'bg-slate-100 text-slate-600'],
            ['label' => 'Approuvés',    'value' => $stats['approved'],     'color' => 'bg-green-100 text-green-600'],
            ['label' => 'En révision',  'value' => $stats['review'],       'color' => 'bg-blue-100 text-blue-600'],
            ['label' => 'Expirés',      'value' => $stats['expired'],      'color' => 'bg-red-100 text-red-600'],
            ['label' => 'Archivés',     'value' => $stats['archived'],     'color' => 'bg-amber-100 text-amber-600'],
            ['label' => 'Confidentiels','value' => $stats['confidential'], 'color' => 'bg-purple-100 text-purple-600'],
            ['label' => 'Brouillons',   'value' => $stats['draft'],        'color' => 'bg-slate-100 text-slate-500'],
            ['label' => 'Expirent bientôt','value' => $stats['expiring_soon'],'color' => 'bg-orange-100 text-orange-600'],
        ] as $kpi)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 text-center">
            <p class="text-2xl font-black {{ explode(' ', $kpi['color'])[1] }}">{{ number_format($kpi['value']) }}</p>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider mt-1">{{ $kpi['label'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

        {{-- Activité par utilisateur --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Activité par utilisateur</h2>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($userActivity as $activity)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-500 shrink-0">
                        {{ strtoupper(substr($activity->user?->full_name ?? 'S', 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate">{{ $activity->user?->full_name ?? 'Système' }}</p>
                        <div class="mt-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            @php $max = $userActivity->first()->total; @endphp
                            <div class="bg-orange-500 h-1.5 rounded-full" style="width: {{ ($activity->total / $max) * 100 }}%"></div>
                        </div>
                    </div>
                    <span class="text-xs font-black text-slate-600 shrink-0">{{ number_format($activity->total) }}</span>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-xs text-slate-300 font-bold">Aucune activité</div>
                @endforelse
            </div>
        </div>

        {{-- Documents par catégorie --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Documents par catégorie</h2>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($byCategory as $cat)
                <div class="flex items-center gap-3 px-5 py-3">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-folder-tree text-purple-500 text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-800 truncate">{{ $cat->category?->name ?? 'Sans catégorie' }}</p>
                        @php $maxCat = $byCategory->first()->total; @endphp
                        <div class="mt-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-purple-500 h-1.5 rounded-full" style="width: {{ ($cat->total / $maxCat) * 100 }}%"></div>
                        </div>
                    </div>
                    <span class="text-xs font-black text-slate-600 shrink-0">{{ number_format($cat->total) }}</span>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-xs text-slate-300 font-bold">Aucune catégorie</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Documents expirés --}}
    @if($expiredDocs->count())
    <div class="bg-white rounded-2xl border border-red-100 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-red-50 bg-red-50/50">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                <h2 class="text-xs font-black text-red-700 uppercase tracking-widest">Documents expirés ({{ $expiredDocs->count() }})</h2>
            </div>
            <a href="{{ route('reports.export-csv', ['expired' => 1]) }}"
               class="text-[9px] font-black text-red-500 hover:underline uppercase tracking-wider">
                Exporter <i class="fa-solid fa-download text-[8px]"></i>
            </a>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($expiredDocs->take(10) as $doc)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="w-8 h-8 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-file-word text-red-400 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('documents.show', $doc) }}" class="text-xs font-bold text-slate-800 hover:text-orange-600 truncate block">{{ $doc->title }}</a>
                    <p class="text-[9px] text-slate-400 mt-0.5">{{ $doc->reference }} · {{ $doc->category?->name ?? 'Général' }}</p>
                </div>
                <span class="text-[9px] font-black text-red-600 shrink-0">{{ $doc->expires_at->format('d/m/Y') }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Documents expirant bientôt --}}
    @if($expiringSoon->count())
    <div class="bg-white rounded-2xl border border-amber-100 shadow-sm overflow-hidden">
        <div class="flex items-center px-5 py-4 border-b border-amber-50 bg-amber-50/50 gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <h2 class="text-xs font-black text-amber-700 uppercase tracking-widest">Expirent dans 30 jours ({{ $expiringSoon->count() }})</h2>
        </div>
        <div class="divide-y divide-slate-50">
            @foreach($expiringSoon as $doc)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock text-amber-400 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('documents.show', $doc) }}" class="text-xs font-bold text-slate-800 hover:text-orange-600 truncate block">{{ $doc->title }}</a>
                    <p class="text-[9px] text-slate-400 mt-0.5">{{ $doc->reference }} · {{ $doc->creator?->full_name ?? '—' }}</p>
                </div>
                <span class="text-[9px] font-black text-amber-600 shrink-0">{{ $doc->expires_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
