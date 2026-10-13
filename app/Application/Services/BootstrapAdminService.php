<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\User;

/** D-10: creates the initial admin from the environment; idempotent. */
final class BootstrapAdminService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {}

    /** @return bool true if it was created, false if it already existed */
    public function ensure(string $username, string $password): bool
    {
        if ($this->users->findByUsername(User::normalizeUsername($username)) !== null) {
            return false;
        }
        $this->users->add(User::createAdmin($username, $this->hasher->hash($password)));

        return true;
    }
}
