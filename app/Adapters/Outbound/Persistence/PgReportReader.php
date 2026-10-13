<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\ReportReader;
use App\Domain\ValueObjects\DateRange;
use Illuminate\Support\Facades\DB;

/** ADR-004: one aggregate query over the frozen values; no members or librarians. */
final class PgReportReader implements ReportReader
{
    public function mostBorrowed(DateRange $range): array
    {
        return DB::table('loan_item as li')
            ->join('loan as l', 'l.id', '=', 'li.loan_id')
            ->where('l.loaned_at', '>=', $range->from->format('Y-m-d H:i:sP'))
            ->where('l.loaned_at', '<', $range->endExclusive()->format('Y-m-d H:i:sP'))
            ->groupBy('li.book_id', 'li.book_title', 'li.category_name')
            ->orderByDesc('times_borrowed')
            ->orderBy('li.book_title')
            ->get(['li.book_id', 'li.book_title', 'li.category_name', DB::raw('count(*) as times_borrowed')])
            ->map(fn ($r) => [
                'book_id' => $r->book_id,
                'book_title' => $r->book_title,
                'category_name' => $r->category_name,
                'times_borrowed' => (int) $r->times_borrowed,
            ])->all();
    }
}
