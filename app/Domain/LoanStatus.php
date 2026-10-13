<?php

namespace App\Domain;

/** Computed loan status (Art. VII): never stored. */
enum LoanStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Closed = 'closed';
}
