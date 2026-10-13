<?php

namespace App\Domain\Exceptions;

final class NotFound extends DomainError
{
    public function __construct(string $what = 'El recurso')
    {
        parent::__construct('NOT_FOUND', "$what no existe.");
    }
}
