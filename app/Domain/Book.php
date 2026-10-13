<?php

namespace App\Domain;

use App\Domain\Exceptions\BookHasOpenLoans;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use DateTimeImmutable;

final class Book
{
    public function __construct(
        public readonly string $id,
        public string $title,
        public string $author,
        public readonly Isbn $isbn,
        public string $categoryId,
        public int $totalCopies,
        public int $availableCopies,
        public ?string $coverKey = null,
        public ?DateTimeImmutable $deletedAt = null,
    ) {}

    /** CA-03.1: los disponibles arrancan iguales al total. */
    public static function create(string $title, string $author, Isbn $isbn, string $categoryId, Quantity $total): self
    {
        return new self(Uuid::v4(), self::cleanTitle($title), self::cleanAuthor($author), $isbn, $categoryId, $total->value, $total->value);
    }

    /** CA-03.3: cambiar el total ajusta los disponibles en la misma diferencia, nunca por debajo de 0. */
    public function update(string $title, string $author, string $categoryId, Quantity $total): void
    {
        $available = $this->availableCopies + ($total->value - $this->totalCopies);
        if ($available < 0) {
            throw new ValidationFailed(
                'El nuevo total dejaría los disponibles por debajo de 0: hay '.($this->totalCopies - $this->availableCopies).' ejemplar(es) prestado(s).',
                'total_copies',
                'menor que los prestados',
            );
        }
        $this->title = self::cleanTitle($title);
        $this->author = self::cleanAuthor($author);
        $this->categoryId = $categoryId;
        $this->totalCopies = $total->value;
        $this->availableCopies = $available;
    }

    public function withdraw(): void
    {
        if ($this->availableCopies < 1) {
            throw new BookNotAvailable($this->title);
        }
        $this->availableCopies--;
    }

    public function restock(): void
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw new \LogicException('Los disponibles no pueden superar el total.');
        }
        $this->availableCopies++;
    }

    /** CA-03.5 / ADR-003: baja lógica, prohibida con préstamos abiertos. */
    public function delete(DateTimeImmutable $now, bool $hasOpenLoans): void
    {
        if ($hasOpenLoans) {
            throw new BookHasOpenLoans();
        }
        $this->deletedAt = $now;
    }

    private static function cleanTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 250) {
            throw new ValidationFailed('El título es obligatorio y tiene máximo 250 caracteres.', 'title', 'inválido');
        }

        return $title;
    }

    private static function cleanAuthor(string $author): string
    {
        $author = trim($author);
        if ($author === '' || mb_strlen($author) > 200) {
            throw new ValidationFailed('El autor es obligatorio y tiene máximo 200 caracteres.', 'author', 'inválido');
        }

        return $author;
    }
}
