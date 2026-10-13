<?php

namespace App\Domain;

use App\Domain\Exceptions\ValidationFailed;
use App\Domain\ValueObjects\DocumentNumber;

final class Member
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullName,
        public readonly DocumentNumber $documentNumber,
    ) {}

    public static function create(string $fullName, DocumentNumber $document): self
    {
        $fullName = trim($fullName);
        if ($fullName === '' || mb_strlen($fullName) > 200) {
            throw new ValidationFailed('El nombre es obligatorio y tiene máximo 200 caracteres.', 'full_name', 'inválido');
        }

        return new self(Uuid::v4(), $fullName, $document);
    }
}
