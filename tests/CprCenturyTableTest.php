<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The claim that CPR's century table is total — that no serial and year pair is
 * undecidable — is what makes Danish birthDate() fully determined. It was a
 * sentence in a JSON file; these assert it.
 */
final class CprCenturyTableTest extends TestCase
{
    #[Test]
    public function everyValidSerialAndYearMatchesExactlyOneRow(): void
    {
        $ambiguous = [];

        for ($serial = 1; $serial <= 9999; $serial++) {
            for ($year = 0; $year <= 99; $year++) {
                if ($this->matchCount($serial, $year) !== 1) {
                    $ambiguous[] = "{$serial}/{$year}";
                }
            }
        }

        $this->assertSame([], $ambiguous);
    }

    #[Test]
    public function serialZeroMatchesNoRow(): void
    {
        $this->assertSame(0, $this->matchCount(0, 75));
    }

    private function matchCount(int $serial, int $year): int
    {
        $matches = 0;

        foreach (SpecData::DK_CENTURY_TABLE as $row) {
            if (
                $serial >= $row['serialMinimum'] && $serial <= $row['serialMaximum']
                && $year >= $row['yearMinimum'] && $year <= $row['yearMaximum']
            ) {
                $matches++;
            }
        }

        return $matches;
    }
}
