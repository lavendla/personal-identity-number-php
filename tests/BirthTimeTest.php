<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\BirthTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BirthTimeTest extends TestCase
{
    #[Test]
    public function aCompleteBirthTimeYieldsABirthDate(): void
    {
        $this->assertSame('1903-12-04', new BirthTime(1903, 12, 4)->toBirthDate()?->iso());
    }

    #[Test]
    public function anUnknownMonthYieldsNoBirthDate(): void
    {
        $this->assertNull(new BirthTime(1915, null, 12)->toBirthDate());
    }

    #[Test]
    public function anUnknownDayYieldsNoBirthDate(): void
    {
        $this->assertNull(new BirthTime(1917, 11, null)->toBirthDate());
    }

    /**
     * January is not an arbitrary stand-in. It is the month that makes
     * "is the day within 1-31" the question an unknown month should ask, so a day
     * of 31 stays possible and 32 does not — and no month is invented for the
     * bearer along the way.
     */
    #[Test]
    public function anUnknownMonthAllowsTheLongestDay(): void
    {
        $this->assertTrue(new BirthTime(1915, null, 31)->isPossible());
    }

    #[Test]
    public function anUnknownMonthStillRejectsADayNoMonthHas(): void
    {
        $this->assertFalse(new BirthTime(1915, null, 32)->isPossible());
    }

    #[Test]
    public function theEarliestPossibleDateFillsBothUnknowns(): void
    {
        $this->assertSame('1980-01-01', new BirthTime(1980, null, null)->earliestPossibleIso());
    }

    /**
     * The floor still applies to a partial birth time, because the year is the one
     * field always present. Asserted here rather than through a fixture because no
     * published number pairs an implausible year with an unknown month.
     */
    #[Test]
    public function aPartialBirthTimeStillCarriesItsYear(): void
    {
        $this->assertSame(1915, new BirthTime(1915, null, null)->year);
    }
}
