<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Page;
use App\Application\Ports\LoanRepository;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Loan;
use App\Domain\LoanItem;
use App\Domain\Uuid;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class PgLoanRepository implements LoanRepository
{
    public const TS = 'Y-m-d H:i:sP';

    public function unreturnedDueDatesOfMember(string $memberId): array
    {
        return DB::table('loan_item as li')
            ->join('loan as l', 'l.id', '=', 'li.loan_id')
            ->where('l.member_id', $memberId)
            ->whereNull('li.returned_at')
            ->pluck('li.due_at')
            ->map(fn ($d) => new DateTimeImmutable($d))
            ->all();
    }

    public function saveNew(Loan $loan, array $books, array $expectedAvailable): void
    {
        DB::transaction(function () use ($loan, $books, $expectedAvailable) {
            foreach ($books as $book) {
                // ADR-005: the available copies read earlier are the concurrency token.
                $affected = DB::table('book')
                    ->where('id', $book->id)
                    ->where('available_copies', $expectedAvailable[$book->id])
                    ->whereNull('deleted_at')
                    ->update(['available_copies' => $book->availableCopies]);
                if ($affected !== 1) {
                    throw new BookNotAvailable($book->title);
                }
            }
            DB::table('loan')->insert([
                'id' => $loan->id,
                'member_id' => $loan->memberId,
                'loaned_by_user_id' => $loan->loanedByUserId,
                'loaned_at' => $loan->loanedAt->format(self::TS),
            ]);
            DB::table('loan_item')->insert(array_map(fn (LoanItem $i) => [
                'id' => $i->id,
                'loan_id' => $loan->id,
                'book_id' => $i->bookId,
                'book_title' => $i->bookTitle,
                'isbn' => $i->isbn,
                'category_name' => $i->categoryName,
                'due_at' => $i->dueAt->format(self::TS),
                'returned_at' => null,
            ], $loan->items));
        });
    }

    public function find(string $id): ?Loan
    {
        if (! Uuid::isValid($id)) {
            return null;
        }
        $row = DB::table('loan')->where('id', $id)->first();
        if (! $row) {
            return null;
        }

        return self::toLoan($row, DB::table('loan_item')->where('loan_id', $id)->orderBy('book_title')->get()->all());
    }

    public function markReturned(string $loanId, string $itemId, string $bookId, DateTimeImmutable $at): void
    {
        DB::transaction(function () use ($loanId, $itemId, $bookId, $at) {
            $affected = DB::table('loan_item')
                ->where('id', $itemId)->where('loan_id', $loanId)->whereNull('returned_at')
                ->update(['returned_at' => $at->format(self::TS)]);
            if ($affected !== 1) {
                throw new ItemAlreadyReturned();
            }
            DB::table('book')->where('id', $bookId)->increment('available_copies');
        });
    }

    public function search(array $filters, DateTimeImmutable $now, int $page, int $size): Page
    {
        $nowTs = $now->format(self::TS);
        $query = DB::table('loan as l');
        if (! empty($filters['from'])) {
            $query->where('l.loaned_at', '>=', $filters['from'].' 00:00:00+00');
        }
        if (! empty($filters['to'])) {
            $query->where('l.loaned_at', '<', (new DateTimeImmutable($filters['to'].' 00:00:00+00'))->modify('+1 day')->format(self::TS));
        }
        if (! empty($filters['member_id'])) {
            $query->where('l.member_id', $filters['member_id']);
        }
        // H-10: the status is computed, never stored.
        $pending = 'select 1 from loan_item p where p.loan_id = l.id and p.returned_at is null';
        $late = $pending.' and p.due_at < ?';
        match ($filters['status'] ?? null) {
            'closed' => $query->whereRaw("not exists ($pending)"),
            'overdue' => $query->whereRaw("exists ($late)", [$nowTs]),
            'open' => $query->whereRaw("exists ($pending)")->whereRaw("not exists ($late)", [$nowTs]),
            default => null,
        };

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('l.loaned_at')->orderBy('l.id')->offset(($page - 1) * $size)->limit($size)->get(['l.*']);
        $items = DB::table('loan_item')->whereIn('loan_id', $rows->pluck('id'))->orderBy('book_title')->get()->groupBy('loan_id');

        $loans = $rows->map(fn ($r) => self::toLoan($r, ($items[$r->id] ?? collect())->all()))->all();

        return new Page($loans, $page, $size, $total);
    }

    public function overdue(DateTimeImmutable $now, int $page, int $size): Page
    {
        $query = DB::table('loan_item as li')
            ->join('loan as l', 'l.id', '=', 'li.loan_id')
            ->join('member as m', 'm.id', '=', 'l.member_id')
            ->whereNull('li.returned_at')
            ->where('li.due_at', '<', $now->format(self::TS));
        $total = (clone $query)->count();
        $rows = $query->orderBy('li.due_at')->orderBy('li.id')
            ->offset(($page - 1) * $size)->limit($size)
            ->get(['li.loan_id', 'li.id as item_id', 'li.book_title', 'm.full_name as member_name', 'li.due_at']);

        $items = $rows->map(function ($r) use ($now) {
            $due = new DateTimeImmutable($r->due_at);

            return [
                'loan_id' => $r->loan_id,
                'item_id' => $r->item_id,
                'book_title' => $r->book_title,
                'member_name' => $r->member_name,
                'due_at' => $due,
                'days_late' => intdiv($now->getTimestamp() - $due->getTimestamp(), 86400),
            ];
        })->all();

        return new Page($items, $page, $size, $total);
    }

    /** @param list<object> $itemRows */
    private static function toLoan(object $row, array $itemRows): Loan
    {
        return new Loan(
            $row->id,
            $row->member_id,
            $row->loaned_by_user_id,
            new DateTimeImmutable($row->loaned_at),
            array_map(fn ($i) => new LoanItem(
                $i->id,
                $i->book_id,
                $i->book_title,
                $i->isbn,
                $i->category_name,
                new DateTimeImmutable($i->due_at),
                $i->returned_at ? new DateTimeImmutable($i->returned_at) : null,
            ), $itemRows),
        );
    }
}
