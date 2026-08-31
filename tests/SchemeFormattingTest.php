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
 *
 * One provider rather than three, plus the coverage test below it, because the
 * docblock above used to claim "every member" while listing four of seven: both
 * Norwegian schemes were added with no row here at all, and Finland would have
 * been the third to slip through. Three separate providers made that invisible
 * -- nothing compared any of them against the enum.
 */
final class SchemeFormattingTest extends TestCase
{
    /** @return iterable<string, array{Scheme, int|null, bool, int}> */
    public static function formatting(): iterable
    {
        // scheme, displaySplit, shortFormElidesCentury, displayElision
        $rows = [
            [Scheme::SePersonalNumber, 8, true, 0],
            [Scheme::SeCoordinationNumber, 8, true, 0],
            [Scheme::SeOrganizationNumber, 6, false, 2],
            [Scheme::DkCprNumber, 6, false, 0],
            [Scheme::NoNationalIdentityNumber, 6, false, 0],
            [Scheme::NoDNumber, 6, false, 0],
            // The only null split. A Finnish code's intermediate character
            // already occupies the position a separator would be inserted at,
            // so the display form is the canonical form -- a split of 6 would
            // render 010280-952L as 010280--952L.
            [Scheme::FiPersonalIdentityCode, null, false, 0],
        ];

        foreach ($rows as $row) {
            yield $row[0]->value => $row;
        }
    }

    /**
     * What three hand-written providers could not do: prove the list is
     * complete. A new Scheme case with no row here now reddens this test instead
     * of silently going unasserted.
     */
    #[Test]
    public function everySchemeHasARowInTheFormattingTable(): void
    {
        // array_values, because the provider yields keyed rows so PHPUnit names
        // each case after its scheme -- and array_map preserves those keys.
        $covered = array_values(
            array_map(static fn(array $row): Scheme => $row[0], [...self::formatting()]),
        );

        $this->assertSame(Scheme::cases(), $covered);
    }

    #[Test]
    #[DataProvider('formatting')]
    public function dropsOnlyTheOrganizationNumberPrefix(
        Scheme $scheme,
        ?int $displaySplit,
        bool $elidesCentury,
        int $displayElision,
    ): void {
        $this->assertSame($displayElision, $scheme->displayElision());
    }

    #[Test]
    #[DataProvider('formatting')]
    public function displaySplitsAfterTheDatePortion(
        Scheme $scheme,
        ?int $displaySplit,
        bool $elidesCentury,
        int $displayElision,
    ): void {
        $this->assertSame($displaySplit, $scheme->displaySplit());
    }

    #[Test]
    #[DataProvider('formatting')]
    public function knowsWhetherItsShortFormElidesACentury(
        Scheme $scheme,
        ?int $displaySplit,
        bool $elidesCentury,
        int $displayElision,
    ): void {
        $this->assertSame($elidesCentury, $scheme->shortFormElidesCentury());
    }
}
