<?php

namespace App\Adapters\Outbound\Security;

use App\Application\Ports\TokenIssuer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/** D-11 / ADR-007: JWT HS256 de 8 horas con `sub` y `role`; sin datos personales. */
final class JwtTokenIssuer implements TokenIssuer
{
    public function __construct(private readonly string $secret) {}

    public function issue(string $userId, string $role): string
    {
        $now = time();

        return JWT::encode(['sub' => $userId, 'role' => $role, 'iat' => $now, 'exp' => $now + self::TTL_SECONDS], $this->secret, 'HS256');
    }

    public function verify(string $token): ?array
    {
        try {
            $claims = (array) JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable) {
            return null;
        }
        if (! isset($claims['sub'], $claims['role'])) {
            return null;
        }

        return ['sub' => (string) $claims['sub'], 'role' => (string) $claims['role']];
    }
}
