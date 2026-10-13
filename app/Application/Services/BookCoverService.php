<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepository;
use App\Application\Ports\CoverStorage;
use App\Domain\Exceptions\NotFound;
use App\Domain\Exceptions\ValidationFailed;

/** CA-03.4: portada JPG/PNG de máximo 2 MB; la base guarda solo una clave opaca. */
final class BookCoverService
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $storage,
    ) {}

    public function upload(string $bookId, string $bytes): string
    {
        $book = $this->books->find($bookId) ?? throw new NotFound('El libro');
        if (strlen($bytes) > self::MAX_BYTES) {
            throw new ValidationFailed('La portada supera el máximo de 2 MB.', 'file', 'mayor de 2 MB');
        }
        $extension = self::detect($bytes)
            ?? throw new ValidationFailed('La portada debe ser una imagen JPG o PNG.', 'file', 'tipo no permitido');

        $old = $book->coverKey;
        $book->coverKey = $this->storage->put($bytes, $extension);
        $this->books->save($book);
        if ($old !== null) {
            $this->storage->delete($old);
        }

        return $book->coverKey;
    }

    /** data-model §7: primero se anula cover_key y se confirma; después se borra el binario. */
    public function remove(string $bookId): void
    {
        $book = $this->books->find($bookId) ?? throw new NotFound('El libro');
        $old = $book->coverKey;
        $book->coverKey = null;
        $this->books->save($book);
        if ($old !== null) {
            $this->storage->delete($old);
        }
    }

    /** @return array{bytes: string, mime: string} */
    public function get(string $bookId): array
    {
        $book = $this->books->find($bookId) ?? throw new NotFound('El libro');
        $bytes = $book->coverKey === null ? null : $this->storage->get($book->coverKey);
        if ($bytes === null) {
            throw new NotFound('La portada');
        }

        return ['bytes' => $bytes, 'mime' => self::detect($bytes) === 'png' ? 'image/png' : 'image/jpeg'];
    }

    /** El tipo se valida por contenido (firma del archivo), no por extensión. */
    public static function detect(string $bytes): ?string
    {
        if (str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        return null;
    }
}
