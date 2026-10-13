<?php

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\ValidationFailed;
use App\Domain\Member;
use App\Domain\User;
use App\Domain\ValueObjects\DocumentNumber;
use PHPUnit\Framework\TestCase;

/** T-06 */
final class MemberAndUserTest extends TestCase
{
    public function test_username_is_lowercased_and_trimmed_ca_02_2(): void
    {
        $this->assertSame('ana', User::createLibrarian('Ana ', 'librarian', 'hash')->username);
    }

    public function test_admin_role_cannot_be_requested_through_api_ca_02_3(): void
    {
        $this->expectException(ValidationFailed::class);
        User::createLibrarian('ana', 'admin', 'hash');
    }

    public function test_role_must_be_admin_or_librarian(): void
    {
        $this->expectException(ValidationFailed::class);
        new User('id', 'ana', 'hash', 'root');
    }

    public function test_member_requires_name(): void
    {
        $this->assertSame('Ana Pérez', Member::create(' Ana Pérez ', new DocumentNumber('1'))->fullName);
        $this->expectException(ValidationFailed::class);
        Member::create('  ', new DocumentNumber('1'));
    }
}
