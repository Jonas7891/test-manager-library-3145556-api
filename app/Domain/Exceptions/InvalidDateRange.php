<?php

namespace App\Domain\Exceptions;

final class InvalidDateRange extends DomainError
{
    public function __construct()
    {
        parent::__construct('INVALID_DATE_RANGE', 'La fecha final no puede ser anterior a la inicial.', [['field' => 'to', 'issue' => 'anterior a from']]);
    }
}
