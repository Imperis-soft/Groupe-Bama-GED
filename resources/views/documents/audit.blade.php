@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Journal d'audit</h1>
            <p class="text-xs text-slate-500 font-medium">{{ $document->title }}</p>
        </div>
        <a href="{{ route('documents.show', $document) }}" class="bg-slate-100 px-4 py-2 rounded-xl text-xs font-bold">Retour au document</a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Action</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Utilisateur</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">Description</th>
                    <th class="px-6 py-3 font-bold text-slate-400 uppercase tracking-widest">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($auditLogs as $log)
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 bg-slate-100 rounded text-[10px] font-bold uppercase {{ $log->action === 'archived' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600' }}">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-bold text-slate-900">{{ $log->user->full_name ?? 'Système' }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-slate-600">{{ $log->description ?? '-' }}</td>
                    <td class="px-6 py-4 font-mono text-slate-400">{{ $log->ip_address ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection