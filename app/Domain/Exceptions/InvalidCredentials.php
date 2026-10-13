<?php

namespace App\Domain\Exceptions;

final class InvalidCredentials extends DomainError
{
    public function __construct()
    {
        parent::__construct('INVALID_CREDENTIALS', 'Usuario o contraseña incorrectos.');
    }
}
