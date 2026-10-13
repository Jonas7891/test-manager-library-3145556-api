<?php

namespace App\Bootstrap;

use App\Adapters\Outbound\Clock\SystemClock;
use App\Adapters\Outbound\Persistence\PgBookRepository;
use App\Adapters\Outbound\Persistence\PgLoanRepository;
use App\Adapters\Outbound\Persistence\PgMemberRepository;
use App\Adapters\Outbound\Persistence\PgReportReader;
use App\Adapters\Outbound\Persistence\PgUserRepository;
use App\Adapters\Outbound\Security\Argon2PasswordHasher;
use App\Adapters\Outbound\Security\JwtTokenIssuer;
use App\Adapters\Outbound\Storage\DiskCoverStorage;
use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\CoverStorage;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\ReportReader;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Artículo III: el ÚNICO lugar donde un puerto se encuentra con su adaptador.
 * Fuera de aquí no se instancia infraestructura.
 */
final class PortBindingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BookRepository::class, PgBookRepository::class);
        $this->app->singleton(MemberRepository::class, PgMemberRepository::class);
        $this->app->singleton(UserRepository::class, PgUserRepository::class);
        $this->app->singleton(LoanRepository::class, PgLoanRepository::class);
        $this->app->singleton(ReportReader::class, PgReportReader::class);
        $this->app->singleton(PasswordHasher::class, Argon2PasswordHasher::class);
        $this->app->singleton(Clock::class, fn () => new SystemClock((int) config('library.clock_offset_days')));
        $this->app->singleton(TokenIssuer::class, fn () => new JwtTokenIssuer((string) config('library.jwt_secret')));
        $this->app->singleton(CoverStorage::class, fn () => new DiskCoverStorage((string) config('library.covers_dir')));
    }
}
