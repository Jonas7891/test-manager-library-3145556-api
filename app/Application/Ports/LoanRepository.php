<?php

namespace App\Application\Ports;

use App\Application\Page;
use App\Domain\Book;
use App\Domain\Loan;
use DateTimeImmutable;

interface LoanRepository
{
    /**
     * Due dates of a member's unreturned lines (Q7).
     *
     * @return list<DateTimeImmutable>
     */
    public function unreturnedDueDatesOfMember(string $memberId): array;

    /**
     * Saves the loan and decrements the copies in ONE transaction. Each book is written only if
     * its available copies are still the ones read (ADR-005); otherwise it rolls back and throws BookNotAvailable.
     *
     * @param  array<string, int>  $expectedAvailable  book_id => available copies read before decrementing
     * @param  list<Book>  $books  libros ya descontados
     */
    public function saveNew(Loan $loan, array $books, array $expectedAvailable): void;

    public function find(string $id): ?Loan;

    /**
     * Marks the line as returned and adds 1 to the book in one transaction, only if it was still unreturned.
     * Throws ItemAlreadyReturned if another request won.
     */
    public function markReturned(string $loanId, string $itemId, string $bookId, DateTimeImmutable $at): void;

    /** Q6: filters `from`, `to` (dates), `status`, `member_id`; newest first. */
    public function search(array $filters, DateTimeImmutable $now, int $page, int $size): Page;

    /** Q8: overdue lines, most days late first. */
    public function overdue(DateTimeImmutable $now, int $page, int $size): Page;
}
