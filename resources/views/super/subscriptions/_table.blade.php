{{-- Tableau d'abonnements : $subscriptions, $showOrganization --}}
@php
    $statusBadges = [
        'active' => 'badge-green', 'trial' => 'badge-violet', 'upcoming' => 'badge-blue',
        'expired' => 'badge-slate', 'cancelled' => 'badge-red',
    ];
@endphp
<div class="overflow-x-auto">
    <table class="data">
        <thead>
        <tr>
            @if($showOrganization)<th>Entreprise</th>@endif
            <th>Offre</th><th>Période</th><th>État</th><th class="!text-right">Montant</th><th>Paiement</th><th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($subscriptions as $sub)
            @php($status = $sub->displayStatus())
            <tr x-data="{ cancel: false }">
                @if($showOrganization)
                    <td>
                        <a href="{{ route('super.organizations.show', $sub->organization) }}" class="flex items-center gap-3 group">
                            <span class="avatar avatar-sm">{{ initials($sub->organization->name) }}</span>
                            <span class="font-bold text-slate-900 group-hover:text-orange-600">{{ $sub->organization->name }}</span>
                        </a>
                    </td>
                @endif
                <td class="font-medium text-slate-800">{{ $sub->plan->name }}</td>
                <td class="whitespace-nowrap">
                    <span class="text-slate-700">{{ $sub->starts_at->format('d/m/Y') }}</span>
                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-300 mx-1.5"></i>
                    <span class="text-slate-700">{{ $sub->ends_at->format('d/m/Y') }}</span>
                </td>
                <td><span class="badge {{ $statusBadges[$status] ?? 'badge-slate' }}"><span class="dot"></span>{{ $sub->displayStatusLabel() }}</span></td>
                <td class="whitespace-nowrap text-right font-semibold text-slate-900 tabular-nums">{{ $sub->formattedAmount() }}</td>
                <td>
                    <p class="text-slate-700">{{ $sub->paymentMethodLabel() }}</p>
                    @if($sub->payment_reference || $sub->paid_at)
                        <p class="text-xs text-slate-500">
                            @if($sub->payment_reference)<span class="font-mono">{{ $sub->payment_reference }}</span>@endif
                            @if($sub->paid_at) · payé le {{ $sub->paid_at->format('d/m/Y') }}@endif
                        </p>
                    @endif
                    @if($sub->notes)<p class="text-xs text-slate-400 whitespace-pre-line mt-0.5">{{ $sub->notes }}</p>@endif
                </td>
                <td class="text-right">
                    @if(in_array($status, ['active', 'trial', 'upcoming']))
                        <button @click="cancel = !cancel" class="btn btn-ghost btn-sm !text-red-600 hover:!bg-red-50">Annuler</button>
                        <form x-show="cancel" x-cloak method="POST" action="{{ route('super.subscriptions.cancel', $sub) }}" class="flex gap-2 mt-2 justify-end">
                            @csrf
                            <input type="text" name="reason" placeholder="Motif" class="field field-sm !w-40">
                            <button class="btn btn-danger-solid btn-sm">Confirmer</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $showOrganization ? 7 : 6 }}">
                    <div class="empty">
                        <div class="empty-icon"><i class="fa-solid fa-receipt"></i></div>
                        <p class="text-sm font-bold text-slate-900">Aucun abonnement</p>
                        <p class="text-sm text-slate-500 mt-1">Les périodes d'abonnement apparaîtront ici.</p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
