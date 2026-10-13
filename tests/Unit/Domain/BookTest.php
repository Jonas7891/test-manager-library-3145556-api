<?php

namespace Tests\Unit\Domain;

use App\Domain\Book;
use App\Domain\Exceptions\BookHasOpenLoans;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** T-04 */
final class BookTest extends TestCase
{
    private function book(int $total = 2): Book
    {
        return Book::create(' Cien años de soledad ', 'García Márquez', new Isbn('9780307474728'), 'cat', new Quantity($total));
    }

    public function test_available_starts_equal_to_total_ca_03_1(): void
    {
        $book = $this->book(4);
        $this->assertSame(4, $book->availableCopies);
        $this->assertSame('Cien años de soledad', $book->title);
        $this->assertNull($book->coverKey);
    }

    public function test_withdraw_never_goes_below_zero_rn_1(): void
    {
        $book = $this->book(1);
        $book->withdraw();
        $this->assertSame(0, $book->availableCopies);

        $this->expectException(BookNotAvailable::class);
        $book->withdraw();
    }

    public function test_restock_never_goes_above_total_ca_07_3(): void
    {
        $book = $this->book(1);
        $this->expectException(\LogicException::class);
        $book->restock();
    }

    public function test_changing_total_moves_available_by_same_difference_ca_03_3(): void
    {
        $book = $this->book(3);
        $book->withdraw();
        $book->update('T', 'A', 'cat', new Quantity(5));
        $this->assertSame(4, $book->availableCopies);

        $book->update('T', 'A', 'cat', new Quantity(1));
        $this->assertSame(0, $book->availableCopies);
    }

    public function test_total_cannot_leave_available_negative_ca_03_3(): void
    {
        $book = $this->book(2);
        $book->withdraw();
        $book->withdraw();
        $this->expectException(ValidationFailed::class);
        $book->update('T', 'A', 'cat', new Quantity(1));
    }

    public function test_logical_delete_blocked_with_open_loans_ca_03_5(): void
    {
        $book = $this->book();
        $book->delete(new DateTimeImmutable('2026-10-13T00:00:00Z'), false);
        $this->assertNotNull($book->deletedAt);

        $this->expectException(BookHasOpenLoans::class);
        $this->book()->delete(new DateTimeImmutable(), true);
    }
}
