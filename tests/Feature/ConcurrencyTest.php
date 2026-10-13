<?php

namespace Tests\Feature;

use App\Application\Ports\BookRepository;
use App\Application\Ports\LoanRepository;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Loan;
use Illuminate\Support\Facades\DB;

/**
 * RNF-2 / ADR-005: dos bibliotecarios prestan el ÚLTIMO ejemplar a la vez.
 * Se reproduce el entrelazado: B lee el libro, A presta y confirma, B intenta guardar con lo leído.
 */
final class ConcurrencyTest extends ApiTestCase
{
    public function test_only_one_of_two_loans_of_the_last_copy_wins(): void
    {
        $admin = $this->token();
        $bookId = $this->book($admin, '9780307474728', 1);
        $memberA = $this->member($admin, 'A-1');
        $memberB = $this->member($admin, 'B-1');

        // B lee el libro: available_copies = 1.
        $books = $this->app->make(BookRepository::class);
        $staleForB = $books->findMany([$bookId]);
        $expectedB = [$bookId => $staleForB[$bookId]->availableCopies];

        // A presta y gana.
        $this->postJson('/api/v1/loans', ['member_id' => $memberA, 'items' => [['book_id' => $bookId]]], $admin)->assertCreated();

        // B guarda con lo que leyó: el testigo ya no coincide.
        $loanB = Loan::open($memberB, 'user', [$bookId], $staleForB, [self::NOVELA => 'Novela'], [], new \DateTimeImmutable());
        try {
            $this->app->make(LoanRepository::class)->saveNew($loanB, array_values($staleForB), $expectedB);
            $this->fail('B no debía poder prestar el último ejemplar');
        } catch (BookNotAvailable $e) {
            $this->assertSame('BOOK_NOT_AVAILABLE', $e->errorCode);
        }

        $this->assertSame(1, DB::table('loan')->count(), 'solo existe el préstamo de A');
        $this->assertSame(0, DB::table('book')->where('id', $bookId)->value('available_copies'));
    }

    public function test_database_check_is_the_last_barrier(): void
    {
        $bookId = $this->book($this->token(), '9780307474728', 1);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('book')->where('id', $bookId)->update(['available_copies' => 2]);
    }
}
