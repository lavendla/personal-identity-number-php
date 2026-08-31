<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use OutOfRangeException;

final class NorwegianCenturyResolver
{
    /**
     * Fodselsnummer and D-number keep separate century tables
     * (Folkeregisterhandboken SS 2-2-1 and SS 2-2-2), because the same
     * individnummer resolves to a different century depending on which series
     * it was drawn from -- individnummer 600 with a two-digit year of 70 is
     * 1870 as a fodselsnummer and 2070 as a D-number. `$schemeId` therefore
     * selects the table, not just the input shape.
     *
     * Null means no row matched, which means the registry cannot have issued
     * that individnummer/year combination -- not that the resulting date is
     * impossible. Neither table is total.
     *
     * Throws for a scheme id with no century table at all, mirroring
     * Scheme::metadata()'s loud failure for missing metadata: a silent null
     * here would hide a wiring bug rather than report a hole in the table.
     */
    public static function resolve(string $schemeId, int $individualNumber, int $twoDigitYear): ?int
    {
        if (! array_key_exists($schemeId, SpecData::NO_CENTURY_ROWS)) {
            throw new OutOfRangeException("no century table for {$schemeId}");
        }

        foreach (SpecData::NO_CENTURY_ROWS[$schemeId] as $row) {
            if (
                $individualNumber >= $row['individualMinimum'] && $individualNumber <= $row['individualMaximum']
                && $twoDigitYear >= $row['yearMinimum'] && $twoDigitYear <= $row['yearMaximum']
            ) {
                return $row['centuryBase'] + $twoDigitYear;
            }
        }

        return null;
    }
}
