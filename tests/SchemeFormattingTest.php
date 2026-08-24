<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * How a canonical form is shaped is a property of the scheme, not of the value
 * object holding it. Asserted here across every member so a scheme added later
 * cannot quietly inherit Sweden's twelve-digit assumptions.
 */
final class SchemeFormattingTest extends TestCase
{
    /** @return list<array{Scheme, int}> */
    public static function displaySplits(): array
    {
        return [
            [Scheme::SePersonalNumber, 8],
            [Scheme::SeCoordinationNumber, 8],
            [Scheme::SeOrganizationNumber, 6],
            [Scheme::DkCprNumber, 6],
        ];
    }

    /** @return list<array{Scheme, bool}> */
    public static function centuryElision(): array
    {
        return [
            [Scheme::SePersonalNumber, true],
            [Scheme::SeCoordinationNumber, true],
            [Scheme::SeOrganizationNumber, false],
            [Scheme::DkCprNumber, false],
        ];
    }

    /** @return list<array{Scheme, int}> */
    public static function displayElisions(): array
    {
        return [
            [Scheme::SePersonalNumber, 0],
            [Scheme::SeCoordinationNumber, 0],
            [Scheme::SeOrganizationNumber, 2],
            [Scheme::DkCprNumber, 0],
        ];
    }

    #[Test]
    #[DataProvider('displayElisions')]
    public function dropsOnlyTheOrganizationNumberPrefix(Scheme $scheme, int $expected): void
    {
        $this->assertSame($expected, $scheme->displayElision());
    }

    #[Test]
    #[DataProvider('displaySplits')]
    public function displaySplitsAfterTheDatePortion(Scheme $scheme, int $expected): void
    {
        $this->assertSame($expected, $scheme->displaySplit());
    }

    #[Test]
    #[DataProvider('centuryElision')]
    public function knowsWhetherItsShortFormElidesACentury(Scheme $scheme, bool $expected): void
    {
        $this->assertSame($expected, $scheme->shortFormElidesCentury());
    }
}
