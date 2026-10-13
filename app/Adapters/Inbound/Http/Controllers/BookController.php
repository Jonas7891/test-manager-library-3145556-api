<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Presenter;
use App\Application\Services\BookCoverService;
use App\Application\Services\ManageBooksService;
use App\Application\Services\SearchBooksService;
use App\Domain\Exceptions\ValidationFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** HU-03 y HU-04 */
final class BookController
{
    public function __construct(private readonly SearchBooksService $search) {}

    public function categories(): JsonResponse
    {
        return Presenter::json($this->search->categories());
    }

    public function index(Request $request): JsonResponse
    {
        $q = $request->validate([
            'q' => ['nullable', 'string', 'max:250'],
            'category_id' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return Presenter::json($this->search->search($q['q'] ?? null, $q['category_id'] ?? null, (int) ($q['page'] ?? 1), (int) ($q['size'] ?? 20)));
    }

    public function show(string $id): JsonResponse
    {
        return Presenter::json($this->present($this->search->get($id)));
    }

    public function store(Request $request, ManageBooksService $books): JsonResponse
    {
        $d = $request->validate([
            'title' => ['required', 'string'],
            'author' => ['required', 'string'],
            'isbn' => ['required', 'string'],
            'category_id' => ['required', 'string'],
            'total_copies' => ['required', 'integer'],
        ]);
        $book = $books->create($d['title'], $d['author'], $d['isbn'], $d['category_id'], (int) $d['total_copies']);

        return Presenter::json($this->present($book), 201);
    }

    public function update(Request $request, string $id, ManageBooksService $books): JsonResponse
    {
        $d = $request->validate([
            'title' => ['required', 'string'],
            'author' => ['required', 'string'],
            'category_id' => ['required', 'string'],
            'total_copies' => ['required', 'integer'],
        ]);
        $book = $books->update($id, $d['title'], $d['author'], $d['category_id'], (int) $d['total_copies']);

        return Presenter::json($this->present($book));
    }

    public function destroy(string $id, ManageBooksService $books): Response
    {
        $books->delete($id);

        return response()->noContent();
    }

    public function uploadCover(Request $request, string $id, BookCoverService $covers): JsonResponse
    {
        $file = $request->file('file');
        if ($file === null || ! $file->isValid()) {
            throw new ValidationFailed('Adjunta la portada en el campo «file» (JPG o PNG, máx. 2 MB).', 'file', 'obligatorio');
        }
        $covers->upload($id, (string) file_get_contents($file->getRealPath()));

        return Presenter::json($this->present($this->search->get($id)));
    }

    public function removeCover(string $id, BookCoverService $covers): Response
    {
        $covers->remove($id);

        return response()->noContent();
    }

    public function cover(string $id, BookCoverService $covers): Response
    {
        $cover = $covers->get($id);

        return response($cover['bytes'], 200, ['Content-Type' => $cover['mime']]);
    }

    private function present(\App\Domain\Book $book): array
    {
        return Presenter::book($book, $this->search->categoryName($book->categoryId));
    }
}
