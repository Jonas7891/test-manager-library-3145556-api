<?php

namespace App\Domain\Exceptions;

final class MemberHasOverdue extends DomainError
{
    public function __construct()
    {
        parent::__construct('MEMBER_HAS_OVERDUE', 'El socio tiene libros vencidos y no puede pedir otro hasta devolverlos.');
    }
}
