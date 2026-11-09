<?php

namespace App\Http\Middleware;

use Closure;

class RoleOnly
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Developer adalah super-role dan dapat melewati pembatasan role
        // pada route apa pun. Pembatasan read/write token developer tetap
        // ditangani oleh RestrictDeveloperTrialToken.
        if (!$user || ($user->role !== 'developer' && !in_array($user->role, $roles, true))) {
            abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}
