@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- Breadcrumb + Header --}}
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 font-medium mb-1">
            <a href="{{ route('documents.index') }}" class="hover:text-orange-600 transition-colors">Documents</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <a href="{{ route('documents.show', $document) }}" class="hover:text-orange-600 transition-colors truncate max-w-[160px]">{{ $document->title }}</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <a href="{{ route('documents.versions', $document) }}" class="hover:text-orange-600 transition-colors">Versions</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <span class="text-slate-600 font-bold">Comparaison</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">
            Comparaison v{{ $comparison['version_a']['number'] }} ↔ v{{ $comparison['version_b']['number'] }}
        </h1>
        <p class="text-xs text-slate-400 mt-1">
            <span class="font-bold text-slate-600">{{ $document->reference }}</span>
            &nbsp;·&nbsp; {{ $document->title }}
        </p>
    </div>

    {{-- Résumé des différences --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="text-sm font-bold text-slate-700 mb-3">
            <i class="fa-solid fa-code-compare text-orange-500 mr-1"></i> Résumé
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="text-center p-3 rounded-lg {{ $comparison['differences']['checksum_changed'] ? 'bg-amber-50 border border-amber-200' : 'bg-green-50 border border-green-200' }}">
                <i class="fa-solid {{ $comparison['differences']['checksum_changed'] ? 'fa-triangle-exclamation text-amber-500' : 'fa-check-circle text-green-500' }} text-lg"></i>
                <p class="text-xs font-bold mt-1 {{ $comparison['differences']['checksum_changed'] ? 'text-amber-700' : 'text-green-700' }}">
                    {{ $comparison['differences']['checksum_changed'] ? 'Contenu modifié' : 'Contenu identique' }}
                </p>
            </div>
            <div class="text-center p-3 rounded-lg bg-slate-50 border border-slate-200">
                @php
                    $sizeDiff = $comparison['differences']['size_diff'];
                    $sizeDiffFormatted = $sizeDiff >= 0 ? '+' . number_format($sizeDiff / 1024, 1) : number_format($sizeDiff / 1024, 1);
                @endphp
                <i class="fa-solid fa-weight-scale text-slate-500 text-lg"></i>
                <p class="text-xs font-bold mt-1 text-slate-700">
                    Différence : {{ $sizeDiffFormatted }} Ko
                </p>
            </div>
            <div class="text-center p-3 rounded-lg {{ $comparison['differences']['same_format'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                <i class="fa-solid fa-file text-slate-500 text-lg"></i>
                <p class="text-xs font-bold mt-1 {{ $comparison['differences']['same_format'] ? 'text-green-700' : 'text-red-700' }}">
                    {{ $comparison['differences']['same_format'] ? 'Même format' : 'Format différent' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Comparaison côte à côte --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Version A --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-black text-sm">A</span>
                <h3 class="text-sm font-bold text-slate-700">Version {{ $comparison['version_a']['number'] }}</h3>
            </div>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Auteur</dt>
                    <dd class="text-slate-700 font-bold">{{ $comparison['version_a']['created_by'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Date</dt>
                    <dd class="text-slate-700">{{ $comparison['version_a']['created_at'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Taille</dt>
                    <dd class="text-slate-700">{{ $comparison['version_a']['size'] ? number_format($comparison['version_a']['size'] / 1024, 1) . ' Ko' : 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Format</dt>
                    <dd class="text-slate-700">{{ $comparison['version_a']['mime_type'] ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Checksum</dt>
                    <dd class="text-slate-700 font-mono text-[10px] truncate max-w-[180px]" title="{{ $comparison['version_a']['checksum'] }}">{{ Str::limit($comparison['version_a']['checksum'], 20) }}</dd>
                </div>
                @if($comparison['version_a']['change_description'])
                <div class="pt-2 border-t border-slate-100">
                    <dt class="text-slate-400 font-medium mb-1">Description</dt>
                    <dd class="text-slate-600 italic">{{ $comparison['version_a']['change_description'] }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Version B --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-700 font-black text-sm">B</span>
                <h3 class="text-sm font-bold text-slate-700">Version {{ $comparison['version_b']['number'] }}</h3>
            </div>
            <dl class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Auteur</dt>
                    <dd class="text-slate-700 font-bold">{{ $comparison['version_b']['created_by'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Date</dt>
                    <dd class="text-slate-700">{{ $comparison['version_b']['created_at'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Taille</dt>
                    <dd class="text-slate-700">{{ $comparison['version_b']['size'] ? number_format($comparison['version_b']['size'] / 1024, 1) . ' Ko' : 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Format</dt>
                    <dd class="text-slate-700">{{ $comparison['version_b']['mime_type'] ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-400 font-medium">Checksum</dt>
                    <dd class="text-slate-700 font-mono text-[10px] truncate max-w-[180px]" title="{{ $comparison['version_b']['checksum'] }}">{{ Str::limit($comparison['version_b']['checksum'], 20) }}</dd>
                </div>
                @if($comparison['version_b']['change_description'])
                <div class="pt-2 border-t border-slate-100">
                    <dt class="text-slate-400 font-medium mb-1">Description</dt>
                    <dd class="text-slate-600 italic">{{ $comparison['version_b']['change_description'] }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('documents.versions', $document) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition-colors">
            <i class="fa-solid fa-arrow-left"></i> Retour aux versions
        </a>
    </div>
</div>
@endsection
