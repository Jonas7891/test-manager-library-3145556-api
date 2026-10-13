<?php

namespace App\Domain\Exceptions;

final class ItemAlreadyReturned extends DomainError
{
    public function __construct()
    {
        parent::__construct('ITEM_ALREADY_RETURNED', 'Esta línea del préstamo ya fue devuelta.');
    }
}
