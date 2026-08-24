<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\CenturyResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CenturyResolverTest extends TestCase
{
    private const string REFERENCE = '2026-08-16';

    #[Test]
    public function itResolvesToTheMostRecentCenturyNotInTheFuture(): void
    {
        $this->assertSame(1987, $this->resolve(87, 1, 1, false));
    }

    #[Test]
    public function itResolvesADateEarlierThisYearToThisCentury(): void
    {
        $this->assertSame(2026, $this->resolve(26, 1, 1, false));
    }

    #[Test]
    public function itResolvesADateLaterThisYearToThePreviousCentury(): void
    {
        // 2026-12-01 has not happened as of the reference date, so the only
        // reading that is not in the future is 1926.
        $this->assertSame(1926, $this->resolve(26, 12, 1, false));
    }

    #[Test]
    public function itSubtractsAFurtherHundredYearsForThePlusSeparator(): void
    {
        $this->assertSame(1887, $this->resolve(87, 1, 1, true));
    }

    #[Test]
    public function itResolvesTheSameInputDifferentlyAgainstDifferentReferenceDates(): void
    {
        $this->assertSame(1970, $this->resolve(70, 1, 1, false));
    }

    #[Test]
    public function itFollowsTheReferenceDateRatherThanTheSystemClock(): void
    {
        $this->assertSame(
            2070,
            CenturyResolver::resolve(70, 1, 1, false, new DateTimeImmutable('2099-08-16')),
        );
    }

    private function resolve(int $year, int $month, int $day, bool $bornOverCenturyAgo): int
    {
        return CenturyResolver::resolve(
            $year,
            $month,
            $day,
            $bornOverCenturyAgo,
            new DateTimeImmutable(self::REFERENCE),
        );
    }
}
