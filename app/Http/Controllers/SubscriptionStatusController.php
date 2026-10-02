<?php

namespace App\Http\Controllers;

class SubscriptionStatusController extends Controller
{
    // Page affichée quand l'entreprise est suspendue ou sans abonnement en cours
    public function expired()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('super.dashboard');
        }

        $organization = $user->organization;
        if ($organization && $organization->canAccess()) {
            return redirect()->route('dashboard');
        }

        return view('subscription.expired', [
            'organization' => $organization,
            'subscription' => $organization?->latestSubscription(),
            'isAdmin'      => $user->hasRole('admin'),
        ]);
    }
}
