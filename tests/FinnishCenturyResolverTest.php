<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\FinnishCenturyResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FinnishCenturyResolverTest extends TestCase
{
    #[Test]
    public function itReadsTheHyphenAsANineteenHundredsBirth(): void
    {
        $this->assertSame(1980, FinnishCenturyResolver::resolve('-', 80));
    }

    #[Test]
    public function itReadsThePlusSignAsAnEighteenHundredsBirth(): void
    {
        $this->assertSame(1880, FinnishCenturyResolver::resolve('+', 80));
    }

    #[Test]
    #[DataProvider('twoThousandsMarkers')]
    public function itReadsTheTwoThousandsMarkers(string $marker): void
    {
        $this->assertSame(2016, FinnishCenturyResolver::resolve($marker, 16));
    }

    /**
     * Y, X, W, V and U were added for 1900s births by decree 690/2022, and only
     * three of them appear in DVV's published identities. The other two are
     * exercised here rather than by a fixture, because no fixture may assert a
     * successful parse for a marker nobody published -- see
     * spec/fixtures/fi/PROVENANCE.md.
     */
    #[Test]
    #[DataProvider('nineteenHundredsMarkers')]
    public function itReadsTheNineteenHundredsMarkers(string $marker): void
    {
        $this->assertSame(1994, FinnishCenturyResolver::resolve($marker, 94));
    }

    /**
     * G is not in the marker set at all. Null rather than an exception: an
     * unassigned marker means the value is not a Finnish code, which is an
     * ordinary refusal.
     */
    #[Test]
    public function itReturnsNullForACharacterTheDecreeNeverAssigned(): void
    {
        $this->assertNull(FinnishCenturyResolver::resolve('G', 94));
    }

    /** @return iterable<array{string}> */
    public static function twoThousandsMarkers(): iterable
    {
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $marker) {
            yield $marker => [$marker];
        }
    }

    /** @return iterable<array{string}> */
    public static function nineteenHundredsMarkers(): iterable
    {
        foreach (['Y', 'X', 'W', 'V', 'U'] as $marker) {
            yield $marker => [$marker];
        }
    }
}
