<?php

namespace App\Domain\Exceptions;

final class ValidationFailed extends DomainError
{
    public function __construct(string $message, string $field = '', string $issue = '')
    {
        parent::__construct('VALIDATION_ERROR', $message, $field === '' ? [] : [['field' => $field, 'issue' => $issue]]);
    }
}
