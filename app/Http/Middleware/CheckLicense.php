<?php

namespace App\Http\Middleware;

use App\Models\License;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLicense
{
    /**
     * Vérifie que la licence du système est valide.
     * Si la licence est expirée, déconnecte l'utilisateur et redirige vers la page d'expiration.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (License::isSystemLicensed()) {
            return $next($request);
        }

        // Déconnecter l'utilisateur si connecté
        if ($request->user()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('license.expired');
    }
}
