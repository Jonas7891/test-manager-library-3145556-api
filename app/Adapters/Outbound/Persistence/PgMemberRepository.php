<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Page;
use App\Application\Ports\MemberRepository;
use App\Domain\Member;
use App\Domain\Uuid;
use App\Domain\ValueObjects\DocumentNumber;
use Illuminate\Support\Facades\DB;

final class PgMemberRepository implements MemberRepository
{
    public function find(string $id): ?Member
    {
        if (! Uuid::isValid($id)) {
            return null;
        }
        $row = DB::table('member')->where('id', $id)->first();

        return $row ? new Member($row->id, $row->full_name, new DocumentNumber($row->document_number)) : null;
    }

    public function documentTaken(string $document): bool
    {
        return DB::table('member')->where('document_number', $document)->exists();
    }

    public function add(Member $member): void
    {
        DB::table('member')->insert([
            'id' => $member->id,
            'full_name' => $member->fullName,
            'document_number' => $member->documentNumber->value,
        ]);
    }

    public function search(?string $q, int $page, int $size): Page
    {
        $query = DB::table('member');
        if ($q !== null && trim($q) !== '') {
            $like = '%'.addcslashes(trim($q), '%_\\').'%';
            $query->where(fn ($w) => $w->where('full_name', 'ilike', $like)->orWhere('document_number', 'ilike', $like));
        }
        $total = (clone $query)->count();
        $items = $query->orderBy('full_name')->orderBy('id')->offset(($page - 1) * $size)->limit($size)->get()
            ->map(fn ($r) => ['id' => $r->id, 'full_name' => $r->full_name, 'document_number' => $r->document_number])
            ->all();

        return new Page($items, $page, $size, $total);
    }
}
