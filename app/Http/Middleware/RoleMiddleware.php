<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    // Vérifier si l'utilisateur a le rôle requis
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if (! method_exists($user, 'hasRole')) {
            abort(403);
        }

        if (! $user->hasRole($role)) {
            abort(403);
        }

        return $next($request);
    }
}
