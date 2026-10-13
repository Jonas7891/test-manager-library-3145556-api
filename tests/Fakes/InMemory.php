<?php

namespace Tests\Fakes;

use App\Application\Page;
use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\ReportReader;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use App\Domain\Book;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Loan;
use App\Domain\Member;
use App\Domain\User;
use App\Domain\ValueObjects\DateRange;
use DateTimeImmutable;

/** Fakes en memoria de los puertos (LSP): los casos de uso se prueban sin base ni framework. */
final class InMemory
{
    public FakeClock $clock;
    public FakeBooks $books;
    public FakeMembers $members;
    public FakeUsers $users;
    public FakeLoans $loans;
    public FakeHasher $hasher;
    public FakeTokens $tokens;

    public function __construct()
    {
        $this->clock = new FakeClock(new DateTimeImmutable('2026-10-14T14:15:00Z'));
        $this->loans = new FakeLoans();
        $this->books = new FakeBooks($this->loans);
        $this->loans->books = $this->books;
        $this->members = new FakeMembers();
        $this->users = new FakeUsers();
        $this->hasher = new FakeHasher();
        $this->tokens = new FakeTokens();
    }
}

final class FakeClock implements Clock
{
    public function __construct(public DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class FakeHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'hashed:'.$plain;
    }

    public function verify(string $plain, string $hash): bool
    {
        return $hash === 'hashed:'.$plain;
    }
}

final class FakeTokens implements TokenIssuer
{
    public function issue(string $userId, string $role): string
    {
        return "token:{$userId}:{$role}";
    }

    public function verify(string $token): ?array
    {
        $parts = explode(':', $token);

        return count($parts) === 3 ? ['sub' => $parts[1], 'role' => $parts[2]] : null;
    }
}

final class FakeBooks implements BookRepository
{
    /** @var array<string, Book> */
    public array $rows = [];

    public array $categories = ['cat-1' => 'Novela', 'cat-2' => 'Ciencia'];

    public function __construct(private readonly FakeLoans $loans) {}

    public function find(string $id): ?Book
    {
        $b = $this->rows[$id] ?? null;

        return $b && $b->deletedAt === null ? clone $b : null;
    }

    public function findMany(array $ids): array
    {
        $found = [];
        foreach ($ids as $id) {
            if ($b = $this->find($id)) {
                $found[$id] = $b;
            }
        }

        return $found;
    }

    public function isbnTakenByActive(string $isbn): bool
    {
        foreach ($this->rows as $b) {
            if ($b->isbn->value === $isbn && $b->deletedAt === null) {
                return true;
            }
        }

        return false;
    }

    public function add(Book $book): void
    {
        $this->rows[$book->id] = clone $book;
    }

    public function save(Book $book): void
    {
        $this->rows[$book->id] = clone $book;
    }

    public function hasOpenLoans(string $bookId): bool
    {
        foreach ($this->loans->rows as $loan) {
            foreach ($loan->items as $i) {
                if ($i->bookId === $bookId && $i->returnedAt === null) {
                    return true;
                }
            }
        }

        return false;
    }

    public function search(?string $q, ?string $categoryId, int $page, int $size): Page
    {
        return new Page([], $page, $size, 0);
    }

    public function categories(): array
    {
        return [];
    }

    public function categoryName(string $categoryId): ?string
    {
        return $this->categories[$categoryId] ?? null;
    }
}

final class FakeMembers implements MemberRepository
{
    /** @var array<string, Member> */
    public array $rows = [];

    public function find(string $id): ?Member
    {
        return $this->rows[$id] ?? null;
    }

    public function documentTaken(string $document): bool
    {
        foreach ($this->rows as $m) {
            if ($m->documentNumber->value === $document) {
                return true;
            }
        }

        return false;
    }

    public function add(Member $member): void
    {
        $this->rows[$member->id] = $member;
    }

    public function search(?string $q, int $page, int $size): Page
    {
        return new Page(array_values($this->rows), $page, $size, count($this->rows));
    }
}

final class FakeUsers implements UserRepository
{
    /** @var array<string, User> */
    public array $rows = [];

    public function findByUsername(string $username): ?User
    {
        return $this->rows[$username] ?? null;
    }

    public function add(User $user): void
    {
        $this->rows[$user->username] = $user;
    }
}

final class FakeLoans implements LoanRepository, ReportReader
{
    /** @var array<string, Loan> */
    public array $rows = [];

    public FakeBooks $books;

    public function unreturnedDueDatesOfMember(string $memberId): array
    {
        $dates = [];
        foreach ($this->rows as $loan) {
            if ($loan->memberId === $memberId) {
                foreach ($loan->items as $i) {
                    if ($i->returnedAt === null) {
                        $dates[] = $i->dueAt;
                    }
                }
            }
        }

        return $dates;
    }

    public function saveNew(Loan $loan, array $books, array $expectedAvailable): void
    {
        foreach ($books as $b) {
            $this->books->rows[$b->id] = clone $b;
        }
        $this->rows[$loan->id] = $loan;
    }

    public function find(string $id): ?Loan
    {
        $loan = $this->rows[$id] ?? null;

        return $loan ? unserialize(serialize($loan)) : null;
    }

    public function markReturned(string $loanId, string $itemId, string $bookId, DateTimeImmutable $at): void
    {
        $item = $this->rows[$loanId]->item($itemId);
        if ($item->returnedAt !== null) {
            throw new ItemAlreadyReturned();
        }
        $item->returnedAt = $at;
        $this->books->rows[$bookId]->restock();
    }

    public function search(array $filters, DateTimeImmutable $now, int $page, int $size): Page
    {
        return new Page(array_values($this->rows), $page, $size, count($this->rows));
    }

    public function overdue(DateTimeImmutable $now, int $page, int $size): Page
    {
        return new Page([], $page, $size, 0);
    }

    public function mostBorrowed(DateRange $range): array
    {
        $rows = [];
        foreach ($this->rows as $loan) {
            if ($loan->loanedAt < $range->from || $loan->loanedAt >= $range->endExclusive()) {
                continue;
            }
            foreach ($loan->items as $i) {
                $key = $i->bookId.'|'.$i->bookTitle.'|'.$i->categoryName;
                $rows[$key] ??= ['book_id' => $i->bookId, 'book_title' => $i->bookTitle, 'category_name' => $i->categoryName, 'times_borrowed' => 0];
                $rows[$key]['times_borrowed']++;
            }
        }
        $rows = array_values($rows);
        usort($rows, fn ($a, $b) => [$b['times_borrowed'], $a['book_title']] <=> [$a['times_borrowed'], $b['book_title']]);

        return $rows;
    }
}
