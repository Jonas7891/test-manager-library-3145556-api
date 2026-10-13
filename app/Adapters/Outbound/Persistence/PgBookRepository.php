<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Page;
use App\Application\Ports\BookRepository;
use App\Domain\Book;
use App\Domain\Uuid;
use App\Domain\ValueObjects\Isbn;
use Illuminate\Support\Facades\DB;

final class PgBookRepository implements BookRepository
{
    public function find(string $id): ?Book
    {
        if (! Uuid::isValid($id)) {
            return null;
        }
        $row = DB::table('book')->where('id', $id)->whereNull('deleted_at')->first();

        return $row ? self::toBook($row) : null;
    }

    public function findMany(array $ids): array
    {
        $ids = array_values(array_filter($ids, fn ($id) => is_string($id) && Uuid::isValid($id)));
        $books = [];
        foreach (DB::table('book')->whereIn('id', $ids)->whereNull('deleted_at')->get() as $row) {
            $books[$row->id] = self::toBook($row);
        }

        return $books;
    }

    public function isbnTakenByActive(string $isbn): bool
    {
        return DB::table('book')->where('isbn', $isbn)->whereNull('deleted_at')->exists();
    }

    public function add(Book $book): void
    {
        DB::table('book')->insert(self::toRow($book) + ['id' => $book->id, 'isbn' => $book->isbn->value]);
    }

    public function save(Book $book): void
    {
        DB::table('book')->where('id', $book->id)->update(self::toRow($book));
    }

    public function hasOpenLoans(string $bookId): bool
    {
        return DB::table('loan_item')->where('book_id', $bookId)->whereNull('returned_at')->exists();
    }

    public function search(?string $q, ?string $categoryId, int $page, int $size): Page
    {
        $query = DB::table('book as b')
            ->join('category as c', 'c.id', '=', 'b.category_id')
            ->whereNull('b.deleted_at');
        if ($q !== null && trim($q) !== '') {
            $like = '%'.addcslashes(trim($q), '%_\\').'%';
            $query->where(fn ($w) => $w->where('b.title', 'ilike', $like)->orWhere('b.author', 'ilike', $like));
        }
        if ($categoryId !== null && $categoryId !== '') {
            $query->where('b.category_id', Uuid::isValid($categoryId) ? $categoryId : '00000000-0000-0000-0000-000000000000');
        }
        $total = (clone $query)->count();
        $rows = $query->orderBy('b.title')->orderBy('b.id')
            ->select('b.*', 'c.name as category_name')
            ->offset(($page - 1) * $size)->limit($size)->get();

        $items = $rows->map(fn ($r) => [
            'id' => $r->id,
            'title' => $r->title,
            'author' => $r->author,
            'isbn' => $r->isbn,
            'category' => ['id' => $r->category_id, 'name' => $r->category_name],
            'total_copies' => (int) $r->total_copies,
            'available_copies' => (int) $r->available_copies,
            'cover_key' => $r->cover_key,
        ])->all();

        return new Page($items, $page, $size, $total);
    }

    public function categories(): array
    {
        return DB::table('category')->orderBy('name')->get(['id', 'name'])
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->all();
    }

    public function categoryName(string $categoryId): ?string
    {
        if (! Uuid::isValid($categoryId)) {
            return null;
        }

        return DB::table('category')->where('id', $categoryId)->value('name');
    }

    private static function toBook(object $row): Book
    {
        return new Book(
            $row->id,
            $row->title,
            $row->author,
            new Isbn($row->isbn),
            $row->category_id,
            (int) $row->total_copies,
            (int) $row->available_copies,
            $row->cover_key,
            $row->deleted_at ? new \DateTimeImmutable($row->deleted_at) : null,
        );
    }

    private static function toRow(Book $book): array
    {
        return [
            'title' => $book->title,
            'author' => $book->author,
            'category_id' => $book->categoryId,
            'total_copies' => $book->totalCopies,
            'available_copies' => $book->availableCopies,
            'cover_key' => $book->coverKey,
            'deleted_at' => $book->deletedAt?->format('Y-m-d H:i:sP'),
        ];
    }
}
