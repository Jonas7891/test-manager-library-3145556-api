<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Domain\Book;
use App\Domain\Exceptions\IsbnTaken;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;

/** HU-03: create, edit and soft-delete books (admin only). */
final class ManageBooksService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly Clock $clock,
    ) {}

    public function create(string $title, string $author, string $isbn, string $categoryId, int $total): Book
    {
        $this->assertCategory($categoryId);
        $book = Book::create($title, $author, new Isbn($isbn), $categoryId, new Quantity($total));
        if ($this->books->isbnTakenByActive($book->isbn->value)) {
            throw new IsbnTaken();
        }
        $this->books->add($book);

        return $book;
    }

    public function update(string $id, string $title, string $author, string $categoryId, int $total): Book
    {
        $book = $this->books->find($id) ?? throw new NotFound('El libro');
        $this->assertCategory($categoryId);
        $book->update($title, $author, $categoryId, new Quantity($total));
        $this->books->save($book);

        return $book;
    }

    public function delete(string $id): void
    {
        $book = $this->books->find($id) ?? throw new NotFound('El libro');
        $book->delete($this->clock->now(), $this->books->hasOpenLoans($id));
        $this->books->save($book);
    }

    private function assertCategory(string $categoryId): void
    {
        if ($this->books->categoryName($categoryId) === null) {
            throw new ValidationFailed('La categoría no existe.', 'category_id', 'inexistente');
        }
    }
}
