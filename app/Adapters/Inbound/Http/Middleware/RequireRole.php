<?php

namespace App\Adapters\Inbound\Http\Middleware;

use App\Adapters\Inbound\Http\AccessDenied;
use Closure;
use Illuminate\Http\Request;

/** Autorización por rol en cada endpoint; un 403 no revela si el recurso existe. */
final class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! in_array($request->attributes->get('role'), $roles, true)) {
            throw AccessDenied::forbidden();
        }

        return $next($request);
    }
}
