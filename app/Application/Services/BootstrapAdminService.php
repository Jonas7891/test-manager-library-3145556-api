<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\User;

/** D-10: crea el admin inicial desde el entorno; idempotente. */
final class BootstrapAdminService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {}

    /** @return bool true si lo creó, false si ya existía */
    public function ensure(string $username, string $password): bool
    {
        if ($this->users->findByUsername(User::normalizeUsername($username)) !== null) {
            return false;
        }
        $this->users->add(User::createAdmin($username, $this->hasher->hash($password)));

        return true;
    }
}
