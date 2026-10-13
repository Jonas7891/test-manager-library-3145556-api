<?php

namespace App\Domain\Exceptions;

final class UsernameTaken extends DomainError
{
    public function __construct()
    {
        parent::__construct('USERNAME_TAKEN', 'Ese nombre de usuario ya está en uso.', [['field' => 'username', 'issue' => 'duplicado']]);
    }
}
