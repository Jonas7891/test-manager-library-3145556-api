<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Domain\Exceptions\NotFound;
use App\Domain\Loan;

/** HU-06: presta y no hace nada más. */
final class LendBooksService
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly BookRepository $books,
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {}

    /** @param list<string> $bookIds */
    public function lend(string $memberId, array $bookIds, string $userId): Loan
    {
        $this->members->find($memberId) ?? throw new NotFound('El socio');

        $books = $this->books->findMany(array_values(array_unique($bookIds)));
        $expected = [];
        $categoryNames = [];
        foreach ($books as $book) {
            $expected[$book->id] = $book->availableCopies;
            $categoryNames[$book->categoryId] ??= (string) $this->books->categoryName($book->categoryId);
        }

        $loan = Loan::open(
            $memberId,
            $userId,
            $bookIds,
            $books,
            $categoryNames,
            $this->loans->unreturnedDueDatesOfMember($memberId),
            $this->clock->now(),
        );
        $this->loans->saveNew($loan, array_values($books), $expected);

        return $loan;
    }
}
