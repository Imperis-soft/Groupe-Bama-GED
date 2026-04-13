@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- ===== HEADER ===== --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors">Documents</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors truncate max-w-[160px]">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Audit</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Journal d'audit</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">{{ $auditLogs->total() }} entrée(s) enregistrée(s)</p>
        </div>
        <a href="{{ route('documents.show', $document) }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour au document
        </a>
    </div>

    {{-- ===== DOC CARD ===== --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-file-word text-orange-500 text-sm"></i>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-black text-slate-800 truncate">{{ $document->title }}</p>
            <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $document->reference }}</p>
        </div>
        <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider shrink-0
            {{ $document->status === 'approved' ? 'bg-green-50 text-green-600' :
               ($document->status === 'review'   ? 'bg-blue-50 text-blue-600' :
               ($document->status === 'archived' ? 'bg-slate-100 text-slate-500' : 'bg-amber-50 text-amber-600')) }}">
            {{ $document->status }}
        </span>
    </div>

    {{-- ===== TIMELINE ===== --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-2 px-5 py-4 border-b border-slate-50">
            <span class="w-2 h-2 rounded-full bg-purple-500"></span>
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-widest">Historique des actions</h2>
        </div>

        @if($auditLogs->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-shield-halved text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucune entrée d'audit</p>
            <p class="text-xs text-slate-300 mt-1">Les actions sur ce document apparaîtront ici</p>
        </div>
        @else

        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/60">
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Action</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Utilisateur</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($auditLogs as $log)
                    @php
                        $actionColors = [
                            'created'  => 'bg-green-50 text-green-600',
                            'updated'  => 'bg-blue-50 text-blue-600',
                            'archived' => 'bg-amber-50 text-amber-600',
                            'deleted'  => 'bg-red-50 text-red-600',
                            'viewed'   => 'bg-slate-100 text-slate-500',
                            'downloaded' => 'bg-purple-50 text-purple-600',
                        ];
                        $color = $actionColors[$log->action] ?? 'bg-slate-100 text-slate-500';
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider {{ $color }}">
                                @if($log->action === 'created') <i class="fa-solid fa-plus text-[8px]"></i>
                                @elseif($log->action === 'updated') <i class="fa-solid fa-pen text-[8px]"></i>
                                @elseif($log->action === 'archived') <i class="fa-solid fa-box-archive text-[8px]"></i>
                                @elseif($log->action === 'deleted') <i class="fa-solid fa-trash text-[8px]"></i>
                                @elseif($log->action === 'downloaded') <i class="fa-solid fa-download text-[8px]"></i>
                                @else <i class="fa-solid fa-bolt text-[8px]"></i>
                                @endif
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[9px] font-black text-slate-500 shrink-0">
                                    {{ strtoupper(substr($log->user->full_name ?? 'S', 0, 1)) }}
                                </div>
                                <span class="text-xs font-bold text-slate-800">{{ $log->user->full_name ?? 'Système' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-slate-700">{{ $log->created_at->format('d/m/Y') }}</p>
                            <p class="text-[9px] text-slate-400 font-mono">{{ $log->created_at->format('H:i:s') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-600 max-w-xs truncate">{{ $log->description ?? '—' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-[10px] text-slate-400 bg-slate-50 px-2 py-0.5 rounded">
                                {{ $log->ip_address ?? '—' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile timeline --}}
        <div class="md:hidden divide-y divide-slate-50">
            @foreach($auditLogs as $log)
            @php
                $actionColors = [
                    'created'    => ['bg' => 'bg-green-100',  'text' => 'text-green-600',  'icon' => 'fa-plus'],
                    'updated'    => ['bg' => 'bg-blue-100',   'text' => 'text-blue-600',   'icon' => 'fa-pen'],
                    'archived'   => ['bg' => 'bg-amber-100',  'text' => 'text-amber-600',  'icon' => 'fa-box-archive'],
                    'deleted'    => ['bg' => 'bg-red-100',    'text' => 'text-red-600',    'icon' => 'fa-trash'],
                    'downloaded' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'icon' => 'fa-download'],
                ];
                $ac = $actionColors[$log->action] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'icon' => 'fa-bolt'];
            @endphp
            <div class="flex items-start gap-3 px-4 py-4">
                <div class="w-8 h-8 rounded-xl {{ $ac['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fa-solid {{ $ac['icon'] }} {{ $ac['text'] }} text-[10px]"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-black text-slate-800 uppercase tracking-wider">{{ $log->action }}</span>
                        <span class="text-[9px] text-slate-400 font-mono shrink-0">{{ $log->created_at->format('d/m H:i') }}</span>
                    </div>
                    <p class="text-xs font-semibold text-slate-600 mt-0.5">{{ $log->user->full_name ?? 'Système' }}</p>
                    @if($log->description)
                    <p class="text-[10px] text-slate-400 mt-1 truncate">{{ $log->description }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($auditLogs->hasPages())
        <div class="px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                {{ $auditLogs->firstItem() }}–{{ $auditLogs->lastItem() }} sur {{ $auditLogs->total() }} entrées
            </p>
            {{ $auditLogs->links() }}
        </div>
        @endif

        @endif
    </div>

</div>
@endsection
