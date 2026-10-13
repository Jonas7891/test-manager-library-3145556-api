<?php

namespace App\Domain;

/** Estado calculado del préstamo (Art. VII): nunca se almacena. */
enum LoanStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Closed = 'closed';
}
