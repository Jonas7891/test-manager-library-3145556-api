<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Presenter;
use App\Application\Services\MembersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** HU-05 */
final class MemberController
{
    public function __construct(private readonly MembersService $members) {}

    public function index(Request $request): JsonResponse
    {
        $q = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return Presenter::json($this->members->search($q['q'] ?? null, (int) ($q['page'] ?? 1), (int) ($q['size'] ?? 20)));
    }

    public function show(string $id): JsonResponse
    {
        $found = $this->members->get($id);

        return Presenter::json(Presenter::member($found['member']) + ['active_loans' => $found['active_loans']]);
    }

    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'full_name' => ['required', 'string'],
            'document_number' => ['required', 'string'],
        ]);

        return Presenter::json(Presenter::member($this->members->create($d['full_name'], $d['document_number'])), 201);
    }
}
