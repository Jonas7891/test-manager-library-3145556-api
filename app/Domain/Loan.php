<?php

namespace App\Domain;

use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\DuplicateBookInLoan;
use App\Domain\Exceptions\MemberHasOverdue;
use App\Domain\Exceptions\MemberLimitExceeded;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\ValidationFailed;
use DateTimeImmutable;

final class Loan
{
    public const LOAN_DAYS = 14;
    public const MAX_UNRETURNED = 3;
    public const MAX_ITEMS = 3;

    /** @param list<LoanItem> $items */
    public function __construct(
        public readonly string $id,
        public readonly string $memberId,
        public readonly string $loanedByUserId,
        public readonly DateTimeImmutable $loanedAt,
        public array $items = [],
    ) {}

    /**
     * HU-06. Member rules are checked before touching any copy; each line
     * takes its copy in the same operation that creates it (Book::withdraw).
     *
     * @param  list<string>  $requestedBookIds  ids pedidos, en orden (puede traer repetidos)
     * @param  array<string, Book>  $books  active books found, by id
     * @param  array<string, string>  $categoryNames  category_id => nombre
     * @param  list<DateTimeImmutable>  $memberUnreturnedDueDates  due dates of the member's unreturned lines
     */
    public static function open(
        string $memberId,
        string $userId,
        array $requestedBookIds,
        array $books,
        array $categoryNames,
        array $memberUnreturnedDueDates,
        DateTimeImmutable $now,
    ): self {
        $count = count($requestedBookIds);
        if ($count < 1 || $count > self::MAX_ITEMS) {
            throw new ValidationFailed('Un préstamo lleva de 1 a 3 libros.', 'items', 'entre 1 y 3');
        }
        if (count(array_unique($requestedBookIds)) !== $count) {
            throw new DuplicateBookInLoan();
        }
        foreach ($memberUnreturnedDueDates as $due) {
            if ($due < $now) {
                throw new MemberHasOverdue();
            }
        }
        $active = count($memberUnreturnedDueDates);
        if ($active + $count > self::MAX_UNRETURNED) {
            throw new MemberLimitExceeded($active, $count);
        }

        $loan = new self(Uuid::v4(), $memberId, $userId, $now);
        foreach ($requestedBookIds as $i => $bookId) {
            $book = $books[$bookId] ?? throw new NotFound('El libro');
            $loan->addItem($book, $categoryNames[$book->categoryId] ?? throw new NotFound('La categoría'), $i);
        }

        return $loan;
    }

    private function addItem(Book $book, string $categoryName, int $index): void
    {
        try {
            $book->withdraw();
        } catch (BookNotAvailable) {
            throw new BookNotAvailable($book->title, "items[{$index}].book_id");
        }
        $this->items[] = new LoanItem(
            Uuid::v4(),
            $book->id,
            $book->title,
            $book->isbn->value,
            $categoryName,
            $this->loanedAt->modify('+'.self::LOAN_DAYS.' days'),
        );
    }

    public function item(string $itemId): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }
        throw new NotFound('La línea del préstamo');
    }

    /** CA-08.1 / CA-07.4: open, overdue o closed, siempre calculado. */
    public function status(DateTimeImmutable $now): LoanStatus
    {
        $pending = array_filter($this->items, fn (LoanItem $i) => $i->returnedAt === null);
        if ($pending === []) {
            return LoanStatus::Closed;
        }
        foreach ($pending as $item) {
            if ($item->isOverdue($now)) {
                return LoanStatus::Overdue;
            }
        }

        return LoanStatus::Open;
    }
}
