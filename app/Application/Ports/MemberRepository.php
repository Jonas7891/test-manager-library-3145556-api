<?php

namespace App\Application\Ports;

use App\Application\Page;
use App\Domain\Member;

interface MemberRepository
{
    public function find(string $id): ?Member;

    public function documentTaken(string $document): bool;

    public function add(Member $member): void;

    public function search(?string $q, int $page, int $size): Page;
}
