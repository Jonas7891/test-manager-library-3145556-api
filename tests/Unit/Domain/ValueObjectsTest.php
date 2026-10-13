<?php

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\InvalidDateRange;
use App\Domain\Exceptions\ValidationFailed;
use App\Domain\ValueObjects\DateRange;
use App\Domain\ValueObjects\DocumentNumber;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use PHPUnit\Framework\TestCase;

/** T-03 */
final class ValueObjectsTest extends TestCase
{
    public function test_isbn_accepts_exactly_13_digits(): void
    {
        $this->assertSame('9780307474728', (new Isbn(' 9780307474728 '))->value);
    }

    public function test_isbn_rejects_other_formats_ca_03_2(): void
    {
        foreach (['978030747472', '97803074747281', '978-0307474728', 'abcdefghijklm'] as $bad) {
            try {
                new Isbn($bad);
                $this->fail("Se aceptó {$bad}");
            } catch (ValidationFailed $e) {
                $this->assertSame('VALIDATION_ERROR', $e->errorCode);
            }
        }
    }

    public function test_quantity_must_be_at_least_one_ca_03_3(): void
    {
        $this->assertSame(1, (new Quantity(1))->value);
        $this->expectException(ValidationFailed::class);
        new Quantity(0);
    }

    public function test_document_number_is_required(): void
    {
        $this->assertSame('123', (new DocumentNumber(' 123 '))->value);
        $this->expectException(ValidationFailed::class);
        new DocumentNumber('   ');
    }

    public function test_date_range_end_before_start_is_rejected_ca_10_2(): void
    {
        $range = DateRange::fromDates('2026-10-01', '2026-10-31');
        $this->assertSame('2026-11-01', $range->endExclusive()->format('Y-m-d'));

        $this->expectException(InvalidDateRange::class);
        DateRange::fromDates('2026-10-31', '2026-10-01');
    }
}
