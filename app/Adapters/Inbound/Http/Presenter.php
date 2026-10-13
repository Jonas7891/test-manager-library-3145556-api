<?php

namespace App\Adapters\Inbound\Http;

use App\Application\Page;
use App\Domain\Book;
use App\Domain\Loan;
use App\Domain\LoanItem;
use App\Domain\Member;
use App\Domain\User;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;

/** Forma JSON del contrato: snake_case, fechas ISO 8601 UTC con Z, sin secretos. */
final class Presenter
{
    public static function json(mixed $data, int $status = 200): JsonResponse
    {
        if ($data instanceof Page) {
            $data = $data->toArray();
        }

        return new JsonResponse($data, $status, [], JSON_UNESCAPED_UNICODE);
    }

    public static function date(?DateTimeImmutable $d): ?string
    {
        return $d?->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    public static function book(Book $b, string $categoryName): array
    {
        return [
            'id' => $b->id,
            'title' => $b->title,
            'author' => $b->author,
            'isbn' => $b->isbn->value,
            'category' => ['id' => $b->categoryId, 'name' => $categoryName],
            'total_copies' => $b->totalCopies,
            'available_copies' => $b->availableCopies,
            'cover_key' => $b->coverKey,
        ];
    }

    public static function member(Member $m): array
    {
        return ['id' => $m->id, 'full_name' => $m->fullName, 'document_number' => $m->documentNumber->value];
    }

    public static function user(User $u): array
    {
        return ['id' => $u->id, 'username' => $u->username, 'role' => $u->role];
    }

    public static function loan(Loan $l, DateTimeImmutable $now): array
    {
        return [
            'id' => $l->id,
            'member_id' => $l->memberId,
            'loaned_at' => self::date($l->loanedAt),
            'status' => $l->status($now)->value,
            'items' => array_map(fn (LoanItem $i) => self::item($i), $l->items),
        ];
    }

    public static function item(LoanItem $i): array
    {
        return [
            'id' => $i->id,
            'book_id' => $i->bookId,
            'book_title' => $i->bookTitle,
            'isbn' => $i->isbn,
            'category_name' => $i->categoryName,
            'due_at' => self::date($i->dueAt),
            'returned_at' => self::date($i->returnedAt),
        ];
    }
}
