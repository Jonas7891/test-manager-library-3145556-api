<?php

namespace App\Application\Services;

use App\Application\Ports\ReportReader;
use App\Domain\ValueObjects\DateRange;

/** HU-10: no members or librarians; groups by frozen values (ADR-004). */
final class MostBorrowedReportService
{
    public function __construct(private readonly ReportReader $reader) {}

    public function report(string $from, string $to): array
    {
        $range = DateRange::fromDates($from, $to);

        return ['from' => $from, 'to' => $to, 'rows' => $this->reader->mostBorrowed($range)];
    }
}
