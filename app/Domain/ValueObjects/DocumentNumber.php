<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationFailed;

final class DocumentNumber
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 30) {
            throw new ValidationFailed('El documento es obligatorio y tiene máximo 30 caracteres.', 'document_number', 'inválido');
        }
        $this->value = $value;
    }
}
