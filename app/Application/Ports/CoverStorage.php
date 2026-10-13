<?php

namespace App\Application\Ports;

interface CoverStorage
{
    /** Guarda el binario y devuelve una clave opaca. */
    public function put(string $bytes, string $extension): string;

    public function get(string $key): ?string;

    public function delete(string $key): void;
}
