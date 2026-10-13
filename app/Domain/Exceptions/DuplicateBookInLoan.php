<?php

namespace App\Domain\Exceptions;

final class DuplicateBookInLoan extends DomainError
{
    public function __construct()
    {
        parent::__construct('DUPLICATE_BOOK_IN_LOAN', 'Un préstamo no puede repetir el mismo libro.', [['field' => 'items', 'issue' => 'libro repetido']]);
    }
}
