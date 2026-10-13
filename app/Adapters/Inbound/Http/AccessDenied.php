<?php

namespace App\Adapters\Inbound\Http;

/** 401 for a missing or invalid token and 403 for an insufficient role (CA-01.3). */
final class AccessDenied extends \RuntimeException
{
    public function __construct(public readonly int $status, public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }

    public static function unauthenticated(): self
    {
        return new self(401, 'INVALID_CREDENTIALS', 'Se requiere un token válido y vigente.');
    }

    public static function forbidden(): self
    {
        return new self(403, 'FORBIDDEN_ROLE', 'Tu rol no permite esta operación.');
    }
}
