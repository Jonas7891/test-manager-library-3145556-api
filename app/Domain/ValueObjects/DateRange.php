<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidDateRange;
use DateTimeImmutable;
use DateTimeZone;

/** Date range, both ends inclusive, read in UTC (H-11). */
final class DateRange
{
    public function __construct(public readonly DateTimeImmutable $from, public readonly DateTimeImmutable $to)
    {
        if ($to < $from) {
            throw new InvalidDateRange();
        }
    }

    public static function fromDates(string $from, string $to): self
    {
        $utc = new DateTimeZone('UTC');

        return new self(
            new DateTimeImmutable($from.' 00:00:00', $utc),
            new DateTimeImmutable($to.' 00:00:00', $utc),
        );
    }

    /** First instant outside the range: the day after `to` at 00:00Z. */
    public function endExclusive(): DateTimeImmutable
    {
        return $this->to->modify('+1 day');
    }
}
