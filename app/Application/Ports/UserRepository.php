<?php

namespace App\Application\Ports;

use App\Domain\User;

interface UserRepository
{
    /** Q10 */
    public function findByUsername(string $username): ?User;

    public function add(User $user): void;
}
