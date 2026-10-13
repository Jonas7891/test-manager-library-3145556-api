<?php

namespace App\Adapters\Inbound\Http\Middleware;

use App\Adapters\Inbound\Http\AccessDenied;
use App\Application\Ports\TokenIssuer;
use Closure;
use Illuminate\Http\Request;

/** CA-01.3: todo endpoint salvo login y health exige token. */
final class JwtAuth
{
    public function __construct(private readonly TokenIssuer $tokens) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $claims = $token ? $this->tokens->verify($token) : null;
        if ($claims === null) {
            throw AccessDenied::unauthenticated();
        }
        $request->attributes->set('user_id', $claims['sub']);
        $request->attributes->set('role', $claims['role']);

        return $next($request);
    }
}
