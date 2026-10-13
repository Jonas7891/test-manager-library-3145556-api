<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\ValidationFailed;

final class Quantity
{
    public function __construct(public readonly int $value)
    {
        if ($value < 1) {
            throw new ValidationFailed('El total de ejemplares debe ser al menos 1.', 'total_copies', 'menor que 1');
        }
    }
}
