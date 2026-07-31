<?php

namespace App\Http\Middleware;

use Closure;

class RoleOnly
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, $roles, true)) {
            abort(403);
        }
        return $next($request);
    }
}
