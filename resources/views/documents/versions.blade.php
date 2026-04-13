@extends('layouts.app')

@section('content')
<div class="space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
                <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors">{{ $document->title }}</a>
                <i class="fa-solid fa-chevron-right text-[8px]"></i>
                <span class="text-slate-600 font-bold">Versions</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Historique des versions</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $versions->total() }} version(s) — Version actuelle : v{{ $document->version }}</p>
        </div>
        <a href="{{ route('documents.show', $document) }}"
           class="inline-flex items-center gap-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all self-start sm:self-auto">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Retour
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

        {{-- Desktop --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Version</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Créé par</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                        <th class="px-6 py-3 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($versions as $version)
                    @php $isCurrent = $version->version_number == $document->version; @endphp
                    <tr class="hover:bg-slate-50/60 transition-colors {{ $isCurrent ? 'bg-orange-50/40' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-black text-slate-800">v{{ $version->version_number }}</span>
                                @if($isCurrent)
                                <span class="px-2 py-0.5 bg-orange-100 text-orange-600 rounded-lg text-[8px] font-black uppercase">Actuelle</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-[9px] font-black text-slate-500 shrink-0">
                                    {{ strtoupper(substr($version->creator->full_name ?? 'S', 0, 1)) }}
                                </div>
                                <span class="text-xs font-bold text-slate-700">{{ $version->creator->full_name ?? 'Système' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-slate-700">{{ $version->created_at->format('d/m/Y') }}</p>
                            <p class="text-[9px] text-slate-400 font-mono">{{ $version->created_at->format('H:i') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-600 max-w-xs truncate">{{ $version->change_description ?? '—' }}</p>
                            @if($version->checksum)
                            <p class="text-[9px] font-mono text-slate-300 mt-0.5 truncate max-w-[120px]">{{ substr($version->checksum, 0, 12) }}...</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if(!$isCurrent)
                            <form action="{{ route('documents.versions.restore', [$document, $version->version_number]) }}" method="POST"
                                  onsubmit="return confirm('Restaurer la version {{ $version->version_number }} ?');" class="inline">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-[9px] font-black uppercase px-3 py-1.5 rounded-lg transition-all">
                                    <i class="fa-solid fa-rotate-left text-[8px]"></i> Restaurer
                                </button>
                            </form>
                            @else
                            <span class="text-[9px] text-slate-400 font-bold">Version actuelle</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="md:hidden divide-y divide-slate-50">
            @foreach($versions as $version)
            @php $isCurrent = $version->version_number == $document->version; @endphp
            <div class="flex items-start gap-3 px-4 py-4 {{ $isCurrent ? 'bg-orange-50/40' : '' }}">
                <div class="w-10 h-10 rounded-xl {{ $isCurrent ? 'bg-orange-100' : 'bg-slate-100' }} flex items-center justify-center shrink-0">
                    <span class="font-mono font-black text-xs {{ $isCurrent ? 'text-orange-600' : 'text-slate-500' }}">v{{ $version->version_number }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-bold text-slate-800">{{ $version->creator->full_name ?? 'Système' }}</p>
                        @if($isCurrent)
                        <span class="px-1.5 py-0.5 bg-orange-100 text-orange-600 rounded text-[8px] font-black uppercase">Actuelle</span>
                        @endif
                    </div>
                    <p class="text-[9px] text-slate-400 mt-0.5">{{ $version->created_at->format('d/m/Y à H:i') }}</p>
                    @if($version->change_description)
                    <p class="text-[10px] text-slate-500 mt-1 truncate">{{ $version->change_description }}</p>
                    @endif
                </div>
                @if(!$isCurrent)
                <form action="{{ route('documents.versions.restore', [$document, $version->version_number]) }}" method="POST" class="inline shrink-0">
                    @csrf
                    <button type="submit" class="text-[9px] font-black text-blue-600 hover:underline uppercase">Restaurer</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>

        @if($versions->hasPages())
        <div class="px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                {{ $versions->firstItem() }}–{{ $versions->lastItem() }} sur {{ $versions->total() }} versions
            </p>
            {{ $versions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
