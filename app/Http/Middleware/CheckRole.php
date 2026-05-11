<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            abort(401, 'Unauthenticated.');
        }

        $userRoles = $request->user()
            ->roles()
            ->pluck('nombre')
            ->toArray();

        // Admin bypass
        if (in_array('admin', $userRoles)) {
            return $next($request);
        }

        foreach ($roles as $rol) {
            if (in_array($rol, $userRoles)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied.');
    }
}