@extends('layouts.app')

@section('content')
<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Services</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">
                {{ $departments->count() }} service(s) · les membres d'un service accèdent aux catégories qui lui sont ouvertes
            </p>
        </div>
    </div>

    <div class="flex items-start gap-3 bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3">
        <i class="fa-solid fa-circle-info text-slate-400 text-sm mt-0.5"></i>
        <p class="text-xs text-slate-500">
            La liste des services est commune à la plateforme et gérée par {{ config('saas.vendor_name') }}.
            Pour chaque service, choisissez son responsable, ses membres et les catégories qu'il peut consulter ou modifier.
            Un service manquant ? Contactez <a href="mailto:{{ config('saas.support_email') }}" class="font-bold text-orange-600 hover:underline">{{ config('saas.support_email') }}</a>.
        </p>
    </div>

    @if($usersWithoutDepartment > 0 && $departments->isNotEmpty())
    <div class="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl px-4 py-3">
        <i class="fa-solid fa-circle-info text-amber-500 text-sm mt-0.5"></i>
        <p class="text-xs text-amber-700">
            <span class="font-black">{{ $usersWithoutDepartment }} utilisateur(s)</span> ne sont rattachés à aucun service :
            ils ne voient que leurs propres documents, ceux partagés avec eux et les catégories publiques.
        </p>
    </div>
    @endif

    @if($departments->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center mb-4">
            <i class="fa-solid fa-sitemap text-orange-400 text-xl"></i>
        </div>
        <p class="text-sm font-black text-slate-700">Organisez votre entreprise en services</p>
        <p class="text-xs text-slate-400 mt-1 max-w-md">
            Aucun service n'est encore disponible. Contactez {{ config('saas.vendor_name') }} pour les activer.
        </p>
    </div>
    @else

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($departments as $department)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-orange-200 transition-all group overflow-hidden flex flex-col">
            <div class="h-1 w-full bg-gradient-to-r from-orange-400 to-orange-600"></div>

            <div class="p-5 flex-1 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-sitemap text-orange-500 text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('departments.edit', $department) }}"
                               class="text-sm font-black text-slate-900 hover:text-orange-600 transition-colors block truncate leading-tight">
                                {{ $department->name }}
                            </a>
                            <p class="text-[10px] text-slate-400 mt-0.5 truncate">
                                <i class="fa-solid fa-user-tie text-[9px] mr-1"></i>
                                {{ $managers->get($managerIds[$department->id] ?? null)?->full_name ?? 'Aucun responsable' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <a href="{{ route('departments.edit', $department) }}"
                           class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600 transition-all" title="Configurer">
                            <i class="fa-solid fa-sliders text-[10px]"></i>
                        </a>
                    </div>
                </div>

                @if($department->description)
                <p class="text-[11px] text-slate-500 leading-relaxed mb-3 line-clamp-2">{{ $department->description }}</p>
                @endif

                {{-- Membres --}}
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex -space-x-2">
                        @foreach($department->users->take(5) as $member)
                        <div class="w-7 h-7 rounded-full bg-slate-900 border-2 border-white flex items-center justify-center text-white text-[9px] font-black"
                             title="{{ $member->full_name }}">
                            {{ strtoupper(substr($member->full_name, 0, 2)) }}
                        </div>
                        @endforeach
                    </div>
                    <span class="text-[10px] font-bold text-slate-500">{{ $department->users_count }} membre(s)</span>
                </div>

                {{-- Accès --}}
                <div class="mt-auto pt-3 border-t border-slate-50">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Accès aux catégories</p>
                    @if($department->categories->isEmpty())
                    <p class="text-[10px] text-slate-300">Aucune catégorie ouverte</p>
                    @else
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($department->categories->take(6) as $category)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[9px] font-bold
                            {{ $category->pivot->access_level === 'edit' ? 'bg-orange-50 text-orange-600' : 'bg-slate-50 text-slate-500' }}"
                            title="{{ \App\Models\Department::ACCESS_LEVELS[$category->pivot->access_level] }}">
                            <i class="fa-solid {{ $category->pivot->access_level === 'edit' ? 'fa-pen' : 'fa-eye' }} text-[8px]"></i>
                            {{ $category->name }}
                        </span>
                        @endforeach
                        @if($department->categories->count() > 6)
                        <span class="px-2 py-1 bg-slate-50 text-slate-400 rounded-lg text-[9px] font-bold">+{{ $department->categories->count() - 6 }}</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
