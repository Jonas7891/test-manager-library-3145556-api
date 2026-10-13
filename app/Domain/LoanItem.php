<?php

namespace App\Domain;

use App\Domain\Exceptions\ItemAlreadyReturned;
use DateTimeImmutable;

/** Loan line: title, ISBN and category are frozen (RN-4). */
final class LoanItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $bookId,
        public readonly string $bookTitle,
        public readonly string $isbn,
        public readonly string $categoryName,
        public readonly DateTimeImmutable $dueAt,
        public ?DateTimeImmutable $returnedAt = null,
    ) {}

    /** CA-07.2: returning twice is an error; it never adds the copy twice. */
    public function markReturned(DateTimeImmutable $now): void
    {
        if ($this->returnedAt !== null) {
            throw new ItemAlreadyReturned();
        }
        $this->returnedAt = $now;
    }

    public function isOverdue(DateTimeImmutable $now): bool
    {
        return $this->returnedAt === null && $this->dueAt < $now;
    }

    public function daysLate(DateTimeImmutable $now): int
    {
        return $this->isOverdue($now) ? intdiv($now->getTimestamp() - $this->dueAt->getTimestamp(), 86400) : 0;
    }
}
