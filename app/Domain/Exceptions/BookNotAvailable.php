<?php

namespace App\Domain\Exceptions;

final class BookNotAvailable extends DomainError
{
    public function __construct(string $title, string $field = 'items')
    {
        parent::__construct('BOOK_NOT_AVAILABLE', "No quedan ejemplares disponibles de «{$title}».", [['field' => $field, 'issue' => 'sin ejemplares']]);
    }
}
