<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationFailed;

final class Isbn
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if (! preg_match('/^\d{13}$/', $value)) {
            throw new ValidationFailed('El ISBN debe tener exactamente 13 dígitos.', 'isbn', 'formato inválido');
        }
        $this->value = $value;
    }
}
