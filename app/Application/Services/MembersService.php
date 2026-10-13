<?php

namespace App\Application\Services;

use App\Application\Page;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Domain\Exceptions\DocumentTaken;
use App\Domain\Exceptions\NotFound;
use App\Domain\Member;
use App\Domain\ValueObjects\DocumentNumber;

/** HU-05: los socios se crean y se consultan; no se eliminan. */
final class MembersService
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly LoanRepository $loans,
    ) {}

    public function create(string $fullName, string $document): Member
    {
        $member = Member::create($fullName, new DocumentNumber($document));
        if ($this->members->documentTaken($member->documentNumber->value)) {
            throw new DocumentTaken();
        }
        $this->members->add($member);

        return $member;
    }

    public function search(?string $q, int $page, int $size): Page
    {
        return $this->members->search($q, max(1, $page), min(Page::MAX_SIZE, max(1, $size)));
    }

    /** @return array{member: Member, active_loans: int} */
    public function get(string $id): array
    {
        $member = $this->members->find($id) ?? throw new NotFound('El socio');

        return ['member' => $member, 'active_loans' => count($this->loans->unreturnedDueDatesOfMember($id))];
    }
}
