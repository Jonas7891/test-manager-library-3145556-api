<?php

namespace App\Domain\Exceptions;

/**
 * Base de los errores de negocio. El código coincide con api-contract.md §2;
 * el estado HTTP lo decide solo el adaptador HTTP.
 */
class DomainError extends \RuntimeException
{
    /** @param list<array{field: string, issue: string}> $details */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}
