<?php

namespace App\Domain\Exceptions;

final class IsbnTaken extends DomainError
{
    public function __construct()
    {
        parent::__construct('ISBN_TAKEN', 'Ya existe un libro activo con ese ISBN.', [['field' => 'isbn', 'issue' => 'duplicado']]);
    }
}
