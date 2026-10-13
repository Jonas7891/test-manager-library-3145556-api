<?php

namespace App\Console\Commands;

use App\Application\Services\BootstrapAdminService;
use Illuminate\Console\Command;

/** D-10: startup creates the initial admin from ADMIN_USERNAME / ADMIN_PASSWORD. */
final class BootstrapAdminCommand extends Command
{
    protected $signature = 'library:bootstrap-admin';

    protected $description = 'Crea el administrador inicial desde las variables de entorno (idempotente)';

    public function handle(BootstrapAdminService $service): int
    {
        $username = (string) config('library.admin_username');
        $password = (string) config('library.admin_password');
        if ($username === '' || $password === '') {
            $this->error('Faltan ADMIN_USERNAME o ADMIN_PASSWORD en el entorno.');

            return self::FAILURE;
        }
        $this->info($service->ensure($username, $password) ? 'Administrador inicial creado.' : 'El administrador ya existía.');

        return self::SUCCESS;
    }
}
