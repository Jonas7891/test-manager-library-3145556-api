<?php

namespace App\Domain\Exceptions;

/**
 * Base of the business errors. The code matches api-contract.md §2;
 * only the HTTP adapter decides the HTTP status.
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
