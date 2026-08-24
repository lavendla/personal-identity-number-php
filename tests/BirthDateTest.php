<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use InvalidArgumentException;
use Lavendla\PersonalIdentityNumber\BirthDate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BirthDateTest extends TestCase
{
    #[Test]
    public function rendersIsoWithZeroPadding(): void
    {
        $this->assertSame('1875-11-09', new BirthDate(1875, 11, 9)->iso());
    }

    #[Test]
    public function convertsToUtcDateTime(): void
    {
        $this->assertSame(
            '1875-11-09 00:00:00 UTC',
            new BirthDate(1875, 11, 9)->toDateTime()->format('Y-m-d H:i:s T'),
        );
    }

    /**
     * The PHP half of the trap in open-threads §3.5: JavaScript's Date.UTC
     * remaps years 0-99 to 1900-1999 and PHP's checkdate does not. Cheap to
     * assert here, expensive to rediscover from a golden diff.
     */
    #[Test]
    public function keepsYearsBelowOneHundredIntact(): void
    {
        $this->assertSame('0012-03-04', new BirthDate(12, 3, 4)->iso());
    }

    /** The same property through date construction, which is where the twin runtime's trap lives. */
    #[Test]
    public function buildsYearsBelowOneHundredWithoutRemapping(): void
    {
        $this->assertSame('0012', new BirthDate(12, 3, 4)->toDateTime()->format('Y'));
    }

    /**
     * setDate() rolls an impossible date forward rather than refusing it, so an
     * unchecked BirthDate(2024, 2, 30) reported 2024-02-30 from iso() and
     * 2024-03-01 from toDateTime() — an object disagreeing with itself. Both
     * schemes validate before constructing, but an invariant held only by
     * convention is one a later caller silently breaks.
     */
    #[Test]
    public function refusesADateThatDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BirthDate(2024, 2, 30);
    }

    #[Test]
    public function acceptsALeapDayInALeapYear(): void
    {
        $this->assertSame('2024-02-29', new BirthDate(2024, 2, 29)->iso());
    }
}
