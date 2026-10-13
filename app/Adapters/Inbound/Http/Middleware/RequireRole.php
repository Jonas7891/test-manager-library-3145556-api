<?php

namespace App\Adapters\Inbound\Http\Middleware;

use App\Adapters\Inbound\Http\AccessDenied;
use Closure;
use Illuminate\Http\Request;

/** Role authorization on every endpoint; a 403 does not reveal whether the resource exists. */
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
