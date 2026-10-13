<?php

namespace Tests\Feature;

use App\Application\Ports\Clock;
use App\Adapters\Outbound\Clock\SystemClock;

/** T-16 (parte 2): HU-06 a HU-10 contra la API real. */
final class LoansContractTest extends ApiTestCase
{
    private function lend(array $h, string $member, array $books)
    {
        return $this->postJson('/api/v1/loans', ['member_id' => $member, 'items' => array_map(fn ($b) => ['book_id' => $b], $books)], $h);
    }

    private function shiftClock(int $days): void
    {
        $this->app->instance(Clock::class, new SystemClock($days));
    }

    public function test_lend_and_return_hu_06_hu_07(): void
    {
        $lib = $this->librarian();
        $book = $this->book($this->token(), '9780307474728', 1);
        $member = $this->member($lib);

        $loan = $this->lend($lib, $member, [$book])->assertCreated()
            ->assertJsonPath('status', 'open')->assertJsonPath('items.0.book_title', 'Cien años de soledad')->assertJsonPath('items.0.returned_at', null);
        $loanId = $loan->json('id');
        $itemId = $loan->json('items.0.id');
        $this->assertSame(14 * 86400, strtotime($loan->json('items.0.due_at')) - strtotime($loan->json('loaned_at')));
        $this->assertStringEndsWith('Z', $loan->json('loaned_at'));

        $other = $this->member($lib, '2002');
        $this->assertErrorBody($this->lend($lib, $other, [$book]), 409, 'BOOK_NOT_AVAILABLE');
        $this->assertSame(1, \DB::table('loan')->count(), 'CA-06.3: no se registra nada');

        $this->postJson("/api/v1/loans/{$loanId}/items/{$itemId}/return", [], $lib)->assertOk()->assertJsonPath('id', $itemId);
        $this->assertErrorBody($this->postJson("/api/v1/loans/{$loanId}/items/{$itemId}/return", [], $lib), 409, 'ITEM_ALREADY_RETURNED');
        $this->getJson("/api/v1/books/{$book}", $lib)->assertJsonPath('available_copies', 1);
        $this->getJson("/api/v1/loans/{$loanId}", $lib)->assertJsonPath('status', 'closed');
    }

    public function test_lending_rules_ca_06_2_ca_06_4(): void
    {
        $admin = $this->token();
        $books = [];
        foreach (range(1, 4) as $i) {
            $books[] = $this->book($admin, '978000000000'.$i, 3, "Libro {$i}");
        }
        $member = $this->member($admin);

        $this->assertErrorBody($this->lend($admin, $member, [$books[0], $books[0]]), 422, 'DUPLICATE_BOOK_IN_LOAN');
        $this->assertErrorBody($this->lend($admin, $member, $books), 422, 'VALIDATION_ERROR');
        $this->lend($admin, $member, [$books[0], $books[1]])->assertCreated();
        $res = $this->lend($admin, $member, [$books[2], $books[3]]);
        $this->assertErrorBody($res, 422, 'MEMBER_LIMIT_EXCEEDED');
        $res->assertJsonPath('details.0.issue', 'active_loans=2');
        $this->getJson("/api/v1/members/{$member}", $admin)->assertJsonPath('active_loans', 2);
        $this->assertErrorBody($this->deleteJson("/api/v1/books/{$books[0]}", [], $admin), 409, 'BOOK_HAS_OPEN_LOANS');
    }

    public function test_overdue_blocks_and_lists_ca_06_5_hu_09(): void
    {
        $admin = $this->token();
        $b1 = $this->book($admin, '9780000000001', 2, 'Uno');
        $b2 = $this->book($admin, '9780000000002', 2, 'Dos');
        $member = $this->member($admin);

        $this->shiftClock(-20);
        $this->lend($admin, $member, [$b1])->assertCreated();
        $this->shiftClock(0);

        $this->assertErrorBody($this->lend($admin, $member, [$b2]), 409, 'MEMBER_HAS_OVERDUE');
        $this->getJson('/api/v1/loans/overdue', $admin)->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('items.0.book_title', 'Uno')->assertJsonPath('items.0.days_late', 6);
        $this->getJson('/api/v1/loans?status=overdue', $admin)->assertJsonPath('total', 1);
        $this->getJson('/api/v1/loans?status=open', $admin)->assertJsonPath('total', 0);
    }

    public function test_report_is_frozen_and_admin_only_hu_10(): void
    {
        $admin = $this->token();
        $book = $this->book($admin, '9780307474728', 3, 'Título viejo');
        $this->lend($admin, $this->member($admin), [$book])->assertCreated();
        $this->putJson("/api/v1/books/{$book}", ['title' => 'Título nuevo', 'author' => 'A', 'category_id' => self::CIENCIA, 'total_copies' => 3], $admin)->assertOk();

        $today = gmdate('Y-m-d');
        $report = $this->getJson("/api/v1/reports/most-borrowed?from={$today}&to={$today}", $admin)->assertOk();
        $report->assertJsonPath('rows.0.book_title', 'Título viejo')->assertJsonPath('rows.0.category_name', 'Novela')->assertJsonPath('rows.0.times_borrowed', 1);
        $this->assertArrayNotHasKey('member_name', $report->json('rows.0'));

        $this->getJson('/api/v1/reports/most-borrowed?from=2020-01-01&to=2020-01-31', $admin)->assertJsonPath('rows', []);
        $this->assertErrorBody($this->getJson('/api/v1/reports/most-borrowed?from=2026-10-31&to=2026-10-01', $admin), 422, 'INVALID_DATE_RANGE');
    }
}
