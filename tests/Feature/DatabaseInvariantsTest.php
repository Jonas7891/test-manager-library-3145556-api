<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** T-18 and ADR-003: whatever the engine can enforce, it enforces (Art. VI). */
final class DatabaseInvariantsTest extends ApiTestCase
{
    public function test_total_copies_must_be_positive_in_the_engine(): void
    {
        $bookId = $this->book($this->token());
        $this->expectException(QueryException::class);
        DB::table('book')->where('id', $bookId)->update(['total_copies' => 0, 'available_copies' => 0]);
    }

    public function test_role_is_checked_by_the_engine(): void
    {
        $this->expectException(QueryException::class);
        DB::table('user')->insert(['id' => '99999999-9999-4999-8999-999999999999', 'username' => 'root', 'password_hash' => 'x', 'role' => 'root']);
    }

    public function test_book_with_loan_lines_cannot_be_physically_deleted(): void
    {
        $admin = $this->token();
        $bookId = $this->book($admin);
        $this->postJson('/api/v1/loans', ['member_id' => $this->member($admin), 'items' => [['book_id' => $bookId]]], $admin)->assertCreated();
        $this->expectException(QueryException::class);
        DB::table('book')->where('id', $bookId)->delete();
    }

    public function test_no_column_has_a_default_and_there_is_no_status_column(): void
    {
        $columns = DB::select("SELECT column_name, column_default FROM information_schema.columns WHERE table_schema = 'library'");
        $this->assertNotEmpty($columns);
        foreach ($columns as $c) {
            $this->assertNull($c->column_default, "{$c->column_name} no debe tener DEFAULT");
            $this->assertNotContains($c->column_name, ['status', 'days_late', 'created_at', 'updated_at']);
        }
    }
}
