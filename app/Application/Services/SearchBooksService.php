<?php

namespace App\Application\Services;

use App\Application\Page;
use App\Application\Ports\BookRepository;
use App\Domain\Book;
use App\Domain\Exceptions\NotFound;

/** HU-04 */
final class SearchBooksService
{
    public function __construct(private readonly BookRepository $books) {}

    public function search(?string $q, ?string $categoryId, int $page, int $size): Page
    {
        return $this->books->search($q, $categoryId, max(1, $page), min(Page::MAX_SIZE, max(1, $size)));
    }

    public function get(string $id): Book
    {
        return $this->books->find($id) ?? throw new NotFound('El libro');
    }

    public function categories(): array
    {
        return $this->books->categories();
    }

    public function categoryName(string $id): string
    {
        return $this->books->categoryName($id) ?? '';
    }
}
