{{-- Badge d'état d'une entreprise : $organization, $current (abonnement en cours ou null) --}}
@if($organization->isSuspended())
    <span class="badge badge-red"><span class="dot"></span> Suspendue</span>
@elseif(!$current)
    <span class="badge badge-slate"><span class="dot"></span> Sans abonnement</span>
@elseif($current->status === 'trial')
    <span class="badge badge-violet"><span class="dot"></span> Essai · {{ $current->daysRemaining() }} j</span>
@else
    <span class="badge badge-green"><span class="dot"></span> Active · {{ $current->daysRemaining() }} j</span>
@endif
