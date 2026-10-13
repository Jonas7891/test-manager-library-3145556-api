<?php

namespace App\Domain\Exceptions;

final class BookHasOpenLoans extends DomainError
{
    public function __construct()
    {
        parent::__construct('BOOK_HAS_OPEN_LOANS', 'El libro tiene préstamos sin devolver y no se puede dar de baja.');
    }
}
