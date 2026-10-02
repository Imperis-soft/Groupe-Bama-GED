<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    // Vérifier si l'utilisateur a au moins un des rôles requis (ex: role:admin,editor)
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! method_exists($user, 'hasRole')) {
            abort(403);
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}
