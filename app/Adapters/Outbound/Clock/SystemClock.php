<?php

namespace App\Adapters\Outbound\Clock;

use App\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

/** D-05 and ADR-006: real UTC time, with an optional shift in days (0 by default). */
final class SystemClock implements Clock
{
    public function __construct(private readonly int $offsetDays = 0) {}

    public function now(): DateTimeImmutable
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $this->offsetDays === 0 ? $now : $now->modify(sprintf('%+d days', $this->offsetDays));
    }
}
