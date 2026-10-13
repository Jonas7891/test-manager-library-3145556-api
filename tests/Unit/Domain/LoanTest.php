<?php

namespace Tests\Unit\Domain;

use App\Domain\Book;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\DuplicateBookInLoan;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\MemberHasOverdue;
use App\Domain\Exceptions\MemberLimitExceeded;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** T-05: the clock is a fixed value, so there is no need to wait 14 days. */
final class LoanTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-10-14T14:15:00Z');
    }

    private function books(int $n, int $copies = 1): array
    {
        $books = [];
        for ($i = 0; $i < $n; $i++) {
            $b = Book::create("Libro {$i}", 'Autor', new Isbn('978000000000'.$i), 'cat', new Quantity($copies));
            $books[$b->id] = $b;
        }

        return $books;
    }

    private function open(array $books, array $due = [], ?array $ids = null): Loan
    {
        return Loan::open('member', 'user', $ids ?? array_keys($books), $books, ['cat' => 'Novela'], $due, $this->now);
    }

    public function test_due_date_is_14_days_and_values_are_frozen_ca_06_6(): void
    {
        $books = $this->books(1);
        $loan = $this->open($books);
        $item = $loan->items[0];

        $this->assertSame('2026-10-28T14:15:00', $item->dueAt->format('Y-m-d\TH:i:s'));
        $this->assertSame('Libro 0', $item->bookTitle);
        $this->assertSame('Novela', $item->categoryName);
        $this->assertSame(0, reset($books)->availableCopies, 'cada línea descuenta 1 ejemplar (CA-06.3)');
    }

    public function test_loan_has_one_to_three_items_ca_06_1(): void
    {
        $this->expectException(ValidationFailed::class);
        $this->open($this->books(4));
    }

    public function test_same_book_twice_is_rejected_ca_06_2(): void
    {
        $books = $this->books(1, 2);
        $id = array_key_first($books);
        $this->expectException(DuplicateBookInLoan::class);
        $this->open($books, [], [$id, $id]);
    }

    public function test_more_than_three_unreturned_is_rejected_with_count_ca_06_4(): void
    {
        $future = $this->now->modify('+5 days');
        try {
            $this->open($this->books(2), [$future, $future]);
            $this->fail('Debía rechazar');
        } catch (MemberLimitExceeded $e) {
            $this->assertSame('active_loans=2', $e->details[0]['issue']);
        }
    }

    public function test_member_with_overdue_cannot_borrow_ca_06_5(): void
    {
        $this->expectException(MemberHasOverdue::class);
        $this->open($this->books(1), [$this->now->modify('-1 day')]);
    }

    public function test_no_copies_left_fails_ca_06_3(): void
    {
        $books = $this->books(1);
        reset($books)->withdraw();
        $this->expectException(BookNotAvailable::class);
        $this->open($books);
    }

    public function test_status_is_calculated_open_overdue_closed_ca_08_1(): void
    {
        $loan = $this->open($this->books(2));
        $this->assertSame(LoanStatus::Open, $loan->status($this->now));
        $this->assertSame(LoanStatus::Overdue, $loan->status($this->now->modify('+15 days')));

        $loan->items[0]->markReturned($this->now);
        $loan->items[1]->markReturned($this->now);
        $this->assertSame(LoanStatus::Closed, $loan->status($this->now->modify('+30 days')), 'CA-07.4');
    }

    public function test_return_twice_fails_ca_07_2(): void
    {
        $loan = $this->open($this->books(1));
        $loan->items[0]->markReturned($this->now);
        $this->expectException(ItemAlreadyReturned::class);
        $loan->items[0]->markReturned($this->now);
    }

    public function test_days_late(): void
    {
        $loan = $this->open($this->books(1));
        $this->assertSame(3, $loan->items[0]->daysLate($this->now->modify('+17 days')));
    }
}
