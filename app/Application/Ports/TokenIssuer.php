<?php

namespace App\Application\Ports;

interface TokenIssuer
{
    public const TTL_SECONDS = 28800;

    public function issue(string $userId, string $role): string;

    /** @return array{sub: string, role: string}|null null si la firma o la vigencia no son válidas */
    public function verify(string $token): ?array;
}
