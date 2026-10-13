<?php

namespace Tests\Unit\Application;

use App\Application\Services\BookCoverService;
use App\Application\Services\CreateLibrarianService;
use App\Application\Services\LendBooksService;
use App\Application\Services\LoginService;
use App\Application\Services\ManageBooksService;
use App\Application\Services\MembersService;
use App\Application\Services\MostBorrowedReportService;
use App\Application\Services\ReturnItemService;
use App\Domain\Exceptions\BookHasOpenLoans;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\DocumentTaken;
use App\Domain\Exceptions\InvalidCredentials;
use App\Domain\Exceptions\InvalidDateRange;
use App\Domain\Exceptions\IsbnTaken;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\MemberHasOverdue;
use App\Domain\Exceptions\UsernameTaken;
use App\Domain\Exceptions\ValidationFailed;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemory;

/** T-07 a T-11: casos de uso contra fakes, sin base de datos ni framework. */
final class UseCasesTest extends TestCase
{
    private InMemory $w;

    protected function setUp(): void
    {
        $this->w = new InMemory();
    }

    private function books(): ManageBooksService
    {
        return new ManageBooksService($this->w->books, $this->w->clock);
    }

    private function lend(): LendBooksService
    {
        return new LendBooksService($this->w->members, $this->w->books, $this->w->loans, $this->w->clock);
    }

    private function member(string $doc = '100'): string
    {
        return (new MembersService($this->w->members, $this->w->loans))->create('Ana Pérez', $doc)->id;
    }

    public function test_login_returns_8h_token_and_hides_which_field_failed_hu_01(): void
    {
        $users = new CreateLibrarianService($this->w->users, $this->w->hasher);
        $users->create('Ana ', 'secreta', 'librarian');
        $login = new LoginService($this->w->users, $this->w->hasher, $this->w->tokens);

        $ok = $login->login('ANA', 'secreta');
        $this->assertSame(28800, $ok['expires_in']);
        $this->assertSame('librarian', $ok['role']);

        foreach ([['ana', 'mala'], ['nadie', 'secreta']] as [$u, $p]) {
            try {
                $login->login($u, $p);
                $this->fail('Debía fallar');
            } catch (InvalidCredentials $e) {
                $this->assertSame('Usuario o contraseña incorrectos.', $e->getMessage());
            }
        }
    }

    public function test_librarian_creation_rules_hu_02(): void
    {
        $users = new CreateLibrarianService($this->w->users, $this->w->hasher);
        $user = $users->create('Ana ', 'x', 'librarian');
        $this->assertSame('ana', $user->username);
        $this->assertNotSame('x', $user->passwordHash, 'la clave nunca se guarda en claro');

        try {
            $users->create('otra', 'x', 'admin');
            $this->fail('admin por API');
        } catch (ValidationFailed $e) {
            $this->assertSame('VALIDATION_ERROR', $e->errorCode);
        }
        $this->expectException(UsernameTaken::class);
        $users->create('ANA', 'y', 'librarian');
    }

    public function test_isbn_unique_among_active_books_hu_03(): void
    {
        $book = $this->books()->create('Uno', 'A', '9780307474728', 'cat-1', 2);
        try {
            $this->books()->create('Dos', 'B', '9780307474728', 'cat-1', 1);
            $this->fail('ISBN repetido');
        } catch (IsbnTaken) {
        }
        $this->books()->delete($book->id);
        $this->assertNotNull($this->books()->create('Dos', 'B', '9780307474728', 'cat-1', 1), 'ADR-003: ISBN de un libro dado de baja se reutiliza');
    }

    public function test_unknown_category_is_validation_error(): void
    {
        $this->expectException(ValidationFailed::class);
        $this->books()->create('Uno', 'A', '9780307474728', 'nope', 1);
    }

    public function test_lend_discounts_copies_and_blocks_last_copy_hu_06(): void
    {
        $book = $this->books()->create('Último', 'A', '9780307474728', 'cat-1', 1);
        $loan = $this->lend()->lend($this->member('1'), [$book->id], 'user');

        $this->assertSame('Novela', $loan->items[0]->categoryName);
        $this->assertSame(0, $this->w->books->rows[$book->id]->availableCopies);

        $this->expectException(BookNotAvailable::class);
        $this->lend()->lend($this->member('2'), [$book->id], 'user');
    }

    public function test_cannot_delete_book_with_open_loans_ca_03_5(): void
    {
        $book = $this->books()->create('Uno', 'A', '9780307474728', 'cat-1', 2);
        $this->lend()->lend($this->member(), [$book->id], 'user');
        $this->expectException(BookHasOpenLoans::class);
        $this->books()->delete($book->id);
    }

    public function test_member_with_overdue_is_blocked_ca_06_5(): void
    {
        $b1 = $this->books()->create('Uno', 'A', '9780000000001', 'cat-1', 2);
        $b2 = $this->books()->create('Dos', 'A', '9780000000002', 'cat-1', 2);
        $member = $this->member();
        $this->lend()->lend($member, [$b1->id], 'user');

        $this->w->clock->now = $this->w->clock->now->modify('+15 days');
        $this->expectException(MemberHasOverdue::class);
        $this->lend()->lend($member, [$b2->id], 'user');
    }

    public function test_return_restocks_once_and_twice_fails_hu_07(): void
    {
        $book = $this->books()->create('Uno', 'A', '9780307474728', 'cat-1', 1);
        $loan = $this->lend()->lend($this->member(), [$book->id], 'user');
        $service = new ReturnItemService($this->w->loans, $this->w->clock);

        $item = $service->return($loan->id, $loan->items[0]->id);
        $this->assertNotNull($item->returnedAt);
        $this->assertSame(1, $this->w->books->rows[$book->id]->availableCopies);

        $this->expectException(ItemAlreadyReturned::class);
        $service->return($loan->id, $loan->items[0]->id);
    }

    public function test_report_keeps_frozen_title_after_rename_adr_004(): void
    {
        $book = $this->books()->create('Título viejo', 'A', '9780307474728', 'cat-1', 3);
        $this->lend()->lend($this->member(), [$book->id], 'user');
        $this->books()->update($book->id, 'Título nuevo', 'A', 'cat-2', 3);

        $report = (new MostBorrowedReportService($this->w->loans))->report('2026-10-01', '2026-10-31');
        $this->assertSame('Título viejo', $report['rows'][0]['book_title']);
        $this->assertSame('Novela', $report['rows'][0]['category_name']);
        $this->assertArrayNotHasKey('member_id', $report['rows'][0], 'CA-10.3: sin socios');

        $this->assertSame([], (new MostBorrowedReportService($this->w->loans))->report('2025-01-01', '2025-01-31')['rows'], 'CA-10.2: rango vacío');
        $this->expectException(InvalidDateRange::class);
        (new MostBorrowedReportService($this->w->loans))->report('2026-10-31', '2026-10-01');
    }

    public function test_document_is_unique_hu_05(): void
    {
        $this->member('55');
        $this->expectException(DocumentTaken::class);
        $this->member('55');
    }

    public function test_cover_type_is_checked_by_content_ca_03_4(): void
    {
        $this->assertSame('png', BookCoverService::detect("\x89PNG\r\n\x1a\nrest"));
        $this->assertSame('jpg', BookCoverService::detect("\xFF\xD8\xFF\xE0rest"));
        $this->assertNull(BookCoverService::detect('GIF89a'));
    }
}
