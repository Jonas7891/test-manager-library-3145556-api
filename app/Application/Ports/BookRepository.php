<?php

namespace App\Application\Ports;

use App\Application\Page;
use App\Domain\Book;

interface BookRepository
{
    /** Active book by id (Q2); null if it does not exist or is soft-deleted. */
    public function find(string $id): ?Book;

    /**
     * Active books for a batch of ids (Q3).
     *
     * @param  list<string>  $ids
     * @return array<string, Book>
     */
    public function findMany(array $ids): array;

    public function isbnTakenByActive(string $isbn): bool;

    public function add(Book $book): void;

    /** Persists title, author, category, total, available copies, cover and soft delete. */
    public function save(Book $book): void;

    public function hasOpenLoans(string $bookId): bool;

    /** Searches active books by partial text and category, ordered by title (Q1). */
    public function search(?string $q, ?string $categoryId, int $page, int $size): Page;

    /** @return list<array{id: string, name: string}> ordered by name (Q4) */
    public function categories(): array;

    public function categoryName(string $categoryId): ?string;
}
