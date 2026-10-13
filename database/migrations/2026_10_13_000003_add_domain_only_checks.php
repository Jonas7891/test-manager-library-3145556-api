<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T-18 (Art. VI): the two invariants marked "pending" in data-model.md §4 move into the engine.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE library.book ADD CONSTRAINT ck_book_total_positive CHECK (total_copies >= 1)');
        DB::statement('ALTER TABLE library."user" ADD CONSTRAINT ck_user_role CHECK (role IN (\'admin\', \'librarian\'))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE library."user" DROP CONSTRAINT IF EXISTS ck_user_role');
        DB::statement('ALTER TABLE library.book DROP CONSTRAINT IF EXISTS ck_book_total_positive');
    }
};
