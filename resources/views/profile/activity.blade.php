@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('profile.show') }}" class="hover:text-orange-600 transition-colors">Mon profil</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Mon activité</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Mon activité</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">{{ $activities->total() }} action(s) enregistrée(s)</p>
        </div>
        <a href="{{ route('profile.show') }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour au profil
        </a>
    </div>

    {{-- TIMELINE --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        @if($activities->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                <i class="fa-solid fa-clock-rotate-left text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-bold text-slate-400">Aucune activité enregistrée</p>
        </div>
        @else

        {{-- Desktop --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/60 border-b border-slate-100">
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Action</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Document</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($activities as $log)
                    @php
                        $colors = [
                            'created'    => 'bg-green-50 text-green-600',
                            'updated'    => 'bg-blue-50 text-blue-600',
                            'viewed'     => 'bg-slate-100 text-slate-500',
                            'downloaded' => 'bg-purple-50 text-purple-600',
                            'archived'   => 'bg-amber-50 text-amber-600',
                            'deleted'    => 'bg-red-50 text-red-600',
                            'approved'   => 'bg-emerald-50 text-emerald-600',
                            'rejected'   => 'bg-red-50 text-red-600',
                            'signed'     => 'bg-indigo-50 text-indigo-600',
                            'shared'     => 'bg-teal-50 text-teal-600',
                        ];
                        $color = $colors[$log->action] ?? 'bg-slate-100 text-slate-500';
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider {{ $color }}">
                                {{ actionLabel($log->action) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5">
                            @if($log->document)
                            <a href="{{ route('documents.show', $log->document) }}"
                               class="text-xs font-bold text-slate-800 hover:text-orange-600 transition-colors truncate block max-w-[200px]">
                                {{ $log->document->title }}
                            </a>
                            <span class="text-[9px] font-mono text-slate-400">{{ $log->document->reference }}</span>
                            @else
                            <span class="text-xs text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5">
                            <p class="text-xs text-slate-500 max-w-xs truncate">{{ $log->description ?? '—' }}</p>
                        </td>
                        <td class="px-6 py-3.5">
                            <p class="text-xs font-bold text-slate-700">{{ $log->created_at->format('d/m/Y') }}</p>
                            <p class="text-[9px] text-slate-400 font-mono">{{ $log->created_at->format('H:i') }}</p>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden divide-y divide-slate-50">
            @foreach($activities as $log)
            <div class="flex items-start gap-3 px-4 py-4">
                <div class="w-8 h-8 rounded-xl bg-orange-50 flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fa-solid fa-bolt text-orange-500 text-[10px]"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-black text-slate-800 uppercase tracking-wider">{{ actionLabel($log->action) }}</span>
                        <span class="text-[9px] text-slate-400 font-mono shrink-0">{{ $log->created_at->format('d/m H:i') }}</span>
                    </div>
                    @if($log->document)
                    <a href="{{ route('documents.show', $log->document) }}"
                       class="text-xs font-semibold text-orange-600 hover:underline truncate block mt-0.5">
                        {{ $log->document->title }}
                    </a>
                    @endif
                    @if($log->description)
                    <p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ $log->description }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($activities->hasPages())
        <div class="px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                {{ $activities->firstItem() }}–{{ $activities->lastItem() }} sur {{ $activities->total() }}
            </p>
            {{ $activities->links() }}
        </div>
        @endif

        @endif
    </div>

</div>
@endsection
