<?php

namespace App\Application\Ports;

use App\Application\Page;
use App\Domain\Book;
use App\Domain\Loan;
use DateTimeImmutable;

interface LoanRepository
{
    /**
     * Vencimientos de las líneas sin devolver de un socio (Q7).
     *
     * @return list<DateTimeImmutable>
     */
    public function unreturnedDueDatesOfMember(string $memberId): array;

    /**
     * Guarda el préstamo y descuenta los ejemplares en UNA transacción. Cada libro se escribe solo si
     * sus disponibles siguen siendo los leídos (ADR-005); si no, revierte todo y lanza BookNotAvailable.
     *
     * @param  array<string, int>  $expectedAvailable  book_id => disponibles leídos antes de descontar
     * @param  list<Book>  $books  libros ya descontados
     */
    public function saveNew(Loan $loan, array $books, array $expectedAvailable): void;

    public function find(string $id): ?Loan;

    /**
     * Marca la línea devuelta y suma 1 al libro en una transacción; solo si seguía sin devolver.
     * Lanza ItemAlreadyReturned si otra petición ganó.
     */
    public function markReturned(string $loanId, string $itemId, string $bookId, DateTimeImmutable $at): void;

    /** Q6: filtros `from`, `to` (fechas), `status`, `member_id`; orden por fecha descendente. */
    public function search(array $filters, DateTimeImmutable $now, int $page, int $size): Page;

    /** Q8: líneas vencidas, más días de retraso primero. */
    public function overdue(DateTimeImmutable $now, int $page, int $size): Page;
}
