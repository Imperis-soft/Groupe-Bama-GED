@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Circuits d'approbation</h1>
            <p class="text-xs text-slate-400 font-medium mt-1">Modèles réutilisables proposés au lancement d'une validation</p>
        </div>
        <a href="{{ route('approval-templates.create') }}"
           class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-500 active:scale-95 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 transition-all self-start sm:self-auto">
            <i class="fa-solid fa-plus text-[10px]"></i> Nouveau circuit
        </a>
    </div>

    @if($templates->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center mb-4">
            <i class="fa-solid fa-diagram-next text-orange-400 text-xl"></i>
        </div>
        <p class="text-sm font-black text-slate-700">Standardisez vos validations</p>
        <p class="text-xs text-slate-400 mt-1 max-w-md">
            Exemple : « Facture » → responsable du service de l'auteur (3 jours), puis responsable Comptabilité (2 jours), puis la Direction générale.
            Les responsables sont retrouvés automatiquement pour chaque document.
        </p>
        <a href="{{ route('approval-templates.create') }}"
           class="mt-5 inline-flex items-center gap-2 bg-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all">
            <i class="fa-solid fa-plus text-[10px]"></i> Créer le premier circuit
        </a>
    </div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach($templates as $template)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('approval-templates.edit', $template) }}" class="text-sm font-black text-slate-900 hover:text-orange-600">{{ $template->name }}</a>
                    @if($template->category)
                    <p class="text-[10px] text-slate-400 mt-0.5"><i class="fa-solid fa-folder text-amber-400 text-[9px] mr-1"></i>Proposé pour « {{ $template->category->name }} »</p>
                    @endif
                    @if($template->description)
                    <p class="text-xs text-slate-500 mt-1.5 line-clamp-2">{{ $template->description }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <a href="{{ route('approval-templates.edit', $template) }}" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-blue-50 hover:text-blue-600" title="Modifier">
                        <i class="fa-solid fa-pen text-[10px]"></i>
                    </a>
                    <form method="POST" action="{{ route('approval-templates.destroy', $template) }}" onsubmit="return confirm('Supprimer le circuit « {{ addslashes($template->name) }} » ? Les validations déjà lancées ne sont pas modifiées.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" title="Supprimer">
                            <i class="fa-solid fa-trash-can text-[10px]"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-1.5 mt-4">
                @foreach($template->steps as $step)
                @if(!$loop->first)<i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>@endif
                <span class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-1 text-[11px] font-bold text-slate-600">
                    <span class="w-4 h-4 rounded bg-orange-500 text-white text-[8px] font-black flex items-center justify-center">{{ $loop->iteration }}</span>
                    {{ match($step['type']) {
                        'user' => $users[$step['user_id'] ?? 0] ?? 'Compte supprimé',
                        'department_manager' => 'Resp. ' . ($departments[$step['department_id'] ?? 0] ?? 'service supprimé'),
                        default => 'Resp. du service de l\'auteur',
                    } }}
                    @if(!empty($step['due_days']))<span class="text-[9px] text-slate-400 font-semibold">{{ $step['due_days'] }} j</span>@endif
                </span>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
