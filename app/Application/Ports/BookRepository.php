<?php

namespace App\Application\Ports;

use App\Application\Page;
use App\Domain\Book;

interface BookRepository
{
    /** Libro activo por id (Q2); null si no existe o está dado de baja. */
    public function find(string $id): ?Book;

    /**
     * Libros activos por lote de ids (Q3).
     *
     * @param  list<string>  $ids
     * @return array<string, Book>
     */
    public function findMany(array $ids): array;

    public function isbnTakenByActive(string $isbn): bool;

    public function add(Book $book): void;

    /** Persiste título, autor, categoría, total, disponibles, portada y baja. */
    public function save(Book $book): void;

    public function hasOpenLoans(string $bookId): bool;

    /** Búsqueda de activos por texto parcial y categoría, orden por título (Q1). */
    public function search(?string $q, ?string $categoryId, int $page, int $size): Page;

    /** @return list<array{id: string, name: string}> orden por nombre (Q4) */
    public function categories(): array;

    public function categoryName(string $categoryId): ?string;
}
