<?php

namespace App\Application;

/** Resultado paginado: { items, page, size, total } (api-contract §1). */
final class Page
{
    public const MAX_SIZE = 50;

    /** @param list<mixed> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $page,
        public readonly int $size,
        public readonly int $total,
    ) {}

    public function toArray(): array
    {
        return ['items' => $this->items, 'page' => $this->page, 'size' => $this->size, 'total' => $this->total];
    }
}
