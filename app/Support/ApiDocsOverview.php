<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ApiDocsOverview
{
    public static function groups()
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn($route) => Str::startsWith($route->uri(), 'api/'))
            ->flatMap(function ($route) {

                $middlewares = $route->gatherMiddleware();
                $roles = [];

                foreach ($middlewares as $middleware) {
                    if (!is_string($middleware)) {
                        continue;
                    }

                    if (!Str::startsWith($middleware, 'role:')) {
                        continue;
                    }

                    $value = Str::after($middleware, 'role:');

                    $roles = array_merge(
                        $roles,
                        array_map('trim', explode(',', $value))
                    );
                }

                $roles = array_values(array_unique(array_filter($roles)));
                sort($roles);

                $hasAuth = collect($middlewares)->contains(function ($middleware) {
                    return is_string($middleware) && (
                        $middleware === 'auth'
                        || Str::startsWith($middleware, 'auth:')
                        || Str::startsWith(
                            $middleware,
                            \Illuminate\Auth\Middleware\Authenticate::class
                        )
                    );
                });

                $key = !empty($roles)
                    ? 'role:' . implode(',', $roles)
                    : ($hasAuth ? 'authenticated' : 'public');

                return collect($route->methods())
                    ->reject(fn($method) => in_array($method, ['HEAD', 'OPTIONS']))
                    ->map(fn($method) => [
                        'key' => $key,
                        'method' => strtoupper($method),
                        'path' => '/' . ltrim($route->uri(), '/'),
                    ]);
            })
            ->groupBy('key')
            ->map(function ($endpoints, $key) {

                $roles = Str::startsWith($key, 'role:')
                    ? explode(',', Str::after($key, 'role:'))
                    : [];

                $title = match ($key) {
                    'public' => 'Public API',
                    'authenticated' => 'Authenticated API',
                    default => collect($roles)
                        ->map(fn($role) => Str::headline($role))
                        ->implode(' & '),
                };

                $description = match ($key) {
                    'public' => 'Endpoint yang tidak memerlukan autentikasi.',
                    'authenticated' => 'Endpoint yang membutuhkan autentikasi pengguna.',
                    default => 'Endpoint untuk role ' . implode(', ', $roles) . '.',
                };

                return [
                    'key' => $key,
                    'title' => $title,
                    'description' => $description,
                    'middleware' => $key,
                    'total' => $endpoints->count(),
                    'endpoints' => $endpoints->values()->all(),
                ];
            })
            ->sortKeys()
            ->values();
    }
}
