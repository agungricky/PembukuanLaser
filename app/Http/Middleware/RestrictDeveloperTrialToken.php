<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RestrictDeveloperTrialToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (!$bearer) {
            return $next($request);
        }

        $token = PersonalAccessToken::findToken($bearer);

        if (!$token || !str_starts_with($token->name, 'docs-trial-')) {
            return $next($request);
        }

        if (
            !$token->tokenable ||
            $token->tokenable->role !== 'developer' ||
            ($token->expires_at && $token->expires_at->isPast())
        ) {
            return response()->json([
                'message' => 'Token trial tidak valid.',
            ], 403);
        }

        if (
            !$token->can('docs:read') ||
            (
                !$request->isMethod('GET') &&
                (
                    !app()->environment('local') ||
                    !$token->can('docs:write')
                )
            )
        ) {
            return response()->json([
                'message' => 'Token trial tidak memiliki izin untuk request ini.',
            ], 403);
        }

        return $next($request);
    }
}
