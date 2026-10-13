<?php

namespace App\Domain;

use App\Domain\Exceptions\ValidationFailed;

final class User
{
    public const ADMIN = 'admin';
    public const LIBRARIAN = 'librarian';

    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly string $role,
    ) {
        if (! in_array($role, [self::ADMIN, self::LIBRARIAN], true)) {
            throw new ValidationFailed('El rol debe ser admin o librarian.', 'role', 'inválido');
        }
    }

    /** CA-02.2: minúsculas y recortado. */
    public static function normalizeUsername(string $username): string
    {
        $username = mb_strtolower(trim($username));
        if ($username === '' || mb_strlen($username) > 120) {
            throw new ValidationFailed('El nombre de usuario es obligatorio y tiene máximo 120 caracteres.', 'username', 'inválido');
        }

        return $username;
    }

    /** CA-02.3: por la API solo se crean bibliotecarios. */
    public static function createLibrarian(string $username, string $role, string $passwordHash): self
    {
        if ($role !== self::LIBRARIAN) {
            throw new ValidationFailed('Por la API solo se pueden crear usuarios con rol librarian.', 'role', 'solo librarian');
        }

        return new self(Uuid::v4(), self::normalizeUsername($username), $passwordHash, self::LIBRARIAN);
    }

    /** D-10: el admin inicial lo crea el arranque desde el entorno, nunca la API. */
    public static function createAdmin(string $username, string $passwordHash): self
    {
        return new self(Uuid::v4(), self::normalizeUsername($username), $passwordHash, self::ADMIN);
    }
}
