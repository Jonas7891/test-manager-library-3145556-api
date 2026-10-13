<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\Exceptions\UsernameTaken;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\User;

/** HU-02 */
final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {}

    public function create(string $username, string $password, string $role): User
    {
        if ($password === '') {
            throw new ValidationFailed('La contraseña es obligatoria.', 'password', 'vacía');
        }
        $user = User::createLibrarian($username, $role, $this->hasher->hash($password));
        if ($this->users->findByUsername($user->username) !== null) {
            throw new UsernameTaken();
        }
        $this->users->add($user);

        return $user;
    }
}
