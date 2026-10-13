<?php

namespace App\Application\Services;

use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Domain\Exceptions\NotFound;
use App\Domain\LoanItem;

/** HU-07: devuelve una línea a la vez. */
final class ReturnItemService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {}

    public function return(string $loanId, string $itemId): LoanItem
    {
        $loan = $this->loans->find($loanId) ?? throw new NotFound('El préstamo');
        $item = $loan->item($itemId);
        $now = $this->clock->now();
        $item->markReturned($now);
        $this->loans->markReturned($loanId, $itemId, $item->bookId, $now);

        return $item;
    }
}
