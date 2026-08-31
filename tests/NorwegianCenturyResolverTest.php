<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\NorwegianCenturyResolver;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NorwegianCenturyResolverTest extends TestCase
{
    #[Test]
    public function itResolvesThe1900SeriesForAFodselsnummer(): void
    {
        $this->assertSame(1985, NorwegianCenturyResolver::resolve('no-national-identity-number', 499, 85));
    }

    #[Test]
    public function itResolvesThe1800SeriesForAFodselsnummer(): void
    {
        $this->assertSame(1870, NorwegianCenturyResolver::resolve('no-national-identity-number', 600, 70));
    }

    #[Test]
    public function itResolvesThe2000SeriesForAFodselsnummer(): void
    {
        $this->assertSame(2020, NorwegianCenturyResolver::resolve('no-national-identity-number', 600, 20));
    }

    #[Test]
    public function itReturnsNullForAFodselsnummerCombinationTheRegistryCannotIssue(): void
    {
        $this->assertNull(NorwegianCenturyResolver::resolve('no-national-identity-number', 600, 45));
    }

    #[Test]
    public function itResolvesTheSameIndividnummerAndYearToADifferentCenturyForADNumberThanForAFodselsnummer(): void
    {
        $this->assertSame(1870, NorwegianCenturyResolver::resolve('no-national-identity-number', 600, 70));
        $this->assertSame(2070, NorwegianCenturyResolver::resolve('no-d-number', 600, 70));
    }

    #[Test]
    public function itResolvesTheBefore2000SeriesForADNumber(): void
    {
        $this->assertSame(1970, NorwegianCenturyResolver::resolve('no-d-number', 400, 70));
    }

    #[Test]
    public function itThrowsForAnUnknownSchemeIdRatherThanSilentlyReturningNull(): void
    {
        $this->expectException(OutOfRangeException::class);

        NorwegianCenturyResolver::resolve('no-such-scheme', 600, 70);
    }
}
