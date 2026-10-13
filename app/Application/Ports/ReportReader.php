<?php

namespace App\Application\Ports;

use App\Domain\ValueObjects\DateRange;

interface ReportReader
{
    /**
     * Una fila por book_id + book_title + category_name congelados (ADR-004).
     *
     * @return list<array{book_id: string, book_title: string, category_name: string, times_borrowed: int}>
     */
    public function mostBorrowed(DateRange $range): array;
}
