<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->corporation) {
            abort(403, 'No tenant context found for the authenticated user.');
        }

        $user->corporation->makeCurrent();

        return $next($request);
    }
}
