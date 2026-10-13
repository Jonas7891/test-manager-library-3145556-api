<?php

namespace App\Application\Ports;

interface TokenIssuer
{
    public const TTL_SECONDS = 28800;

    public function issue(string $userId, string $role): string;

    /** @return array{sub: string, role: string}|null null if the signature or expiry is not valid */
    public function verify(string $token): ?array;
}
