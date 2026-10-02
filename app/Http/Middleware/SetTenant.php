<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Définit l'entreprise courante et vérifie qu'elle a accès à l'application
 * (non suspendue, abonnement en cours).
 */
class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admin : il n'appartient à aucune entreprise, il "entre" dans l'une d'elles
        if ($user->isSuperAdmin()) {
            $organization = Organization::find($request->session()->get('acting_organization_id'));
            if (!$organization) {
                return redirect()->route('super.dashboard');
            }
            Tenant::set($organization);
            return $next($request);
        }

        if (!$user->is_active) {
            return $this->logout($request, 'Votre compte a été désactivé. Contactez votre administrateur.');
        }

        $organization = $user->organization;
        if (!$organization) {
            return $this->logout($request, 'Votre compte n\'est rattaché à aucune entreprise.');
        }

        Tenant::set($organization);

        if (!$organization->canAccess()) {
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        Tenant::clear();
    }

    private function logout(Request $request, string $message): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
