<?php

namespace App\Domain\Exceptions;

final class MemberLimitExceeded extends DomainError
{
    public function __construct(int $active, int $requested)
    {
        parent::__construct(
            'MEMBER_LIMIT_EXCEEDED',
            "El socio tiene {$active} libro(s) sin devolver; con {$requested} más superaría el máximo de 3.",
            [['field' => 'member_id', 'issue' => "active_loans={$active}"]],
        );
    }
}
