<?php

namespace App\Application\Services;

use App\Application\Page;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Domain\Exceptions\NotFound;
use App\Domain\Loan;
use DateTimeImmutable;

/** HU-08 y HU-09: consultas de préstamos (solo lectura). */
final class LoanQueriesService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->clock->now();
    }

    public function get(string $id): Loan
    {
        return $this->loans->find($id) ?? throw new NotFound('El préstamo');
    }

    public function search(array $filters, int $page, int $size): Page
    {
        return $this->loans->search($filters, $this->clock->now(), max(1, $page), min(Page::MAX_SIZE, max(1, $size)));
    }

    public function overdue(int $page, int $size): Page
    {
        return $this->loans->overdue($this->clock->now(), max(1, $page), min(Page::MAX_SIZE, max(1, $size)));
    }
}
