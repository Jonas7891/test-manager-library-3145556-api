<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Presenter;
use App\Application\Services\LendBooksService;
use App\Application\Services\LoanQueriesService;
use App\Application\Services\ReturnItemService;
use App\Domain\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-06, HU-07, HU-08 y HU-09 */
final class LoanController
{
    public function __construct(private readonly LoanQueriesService $queries) {}

    public function store(Request $request, LendBooksService $service): JsonResponse
    {
        $d = $request->validate([
            'member_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:3'],
            'items.*.book_id' => ['required', 'uuid'],
        ]);
        $loan = $service->lend($d['member_id'], array_column($d['items'], 'book_id'), $request->attributes->get('user_id'));

        return Presenter::json(Presenter::loan($loan, $this->queries->now()), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $f = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:open,overdue,closed'],
            'member_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $page = $this->queries->search($f, (int) ($f['page'] ?? 1), (int) ($f['size'] ?? 20));
        $now = $this->queries->now();

        return Presenter::json([
            'items' => array_map(fn (Loan $l) => Presenter::loan($l, $now), $page->items),
            'page' => $page->page,
            'size' => $page->size,
            'total' => $page->total,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return Presenter::json(Presenter::loan($this->queries->get($id), $this->queries->now()));
    }

    public function returnItem(string $loanId, string $itemId, ReturnItemService $service): JsonResponse
    {
        return Presenter::json(Presenter::item($service->return($loanId, $itemId)));
    }

    public function overdue(Request $request): JsonResponse
    {
        $q = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $page = $this->queries->overdue((int) ($q['page'] ?? 1), (int) ($q['size'] ?? 20));

        return Presenter::json([
            'items' => array_map(fn (array $r) => ['due_at' => Presenter::date($r['due_at'])] + $r, $page->items),
            'page' => $page->page,
            'size' => $page->size,
            'total' => $page->total,
        ]);
    }
}
