<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\UserRepository;
use App\Domain\User;
use Illuminate\Support\Facades\DB;

final class PgUserRepository implements UserRepository
{
    public function findByUsername(string $username): ?User
    {
        $row = DB::table('user')->where('username', $username)->first();

        return $row ? new User($row->id, $row->username, $row->password_hash, $row->role) : null;
    }

    public function add(User $user): void
    {
        DB::table('user')->insert([
            'id' => $user->id,
            'username' => $user->username,
            'password_hash' => $user->passwordHash,
            'role' => $user->role,
        ]);
    }
}
