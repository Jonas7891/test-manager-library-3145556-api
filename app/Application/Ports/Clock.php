<?php

namespace App\Application\Ports;

use DateTimeImmutable;

interface Clock
{
    /** Instante actual en UTC. */
    public function now(): DateTimeImmutable;
}
