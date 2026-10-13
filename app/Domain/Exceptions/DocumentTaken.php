<?php

namespace App\Domain\Exceptions;

final class DocumentTaken extends DomainError
{
    public function __construct()
    {
        parent::__construct('DOCUMENT_TAKEN', 'Ya existe un socio con ese documento.', [['field' => 'document_number', 'issue' => 'duplicado']]);
    }
}
