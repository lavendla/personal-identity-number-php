<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;

final class FinnishCenturyResolver
{
    /**
     * Finland states the century outright, in one character, rather than
     * encoding it in a serial the way Denmark and Norway do. So there is no
     * table to search and no year range to bound: the marker alone decides, and
     * the two-digit year is simply added to it.
     *
     * Null means the character is not one decree 690/2022 assigned to any
     * century, which means the value is not a Finnish code -- an ordinary
     * refusal, not a hole in a table, which is why this returns null where
     * NorwegianCenturyResolver::resolve() throws for an unknown scheme id.
     *
     * Under decree 690/2022 the marker is part of the identity, not
     * punctuation: a code is no longer unique without it, so it can never be
     * discarded the way a Swedish separator can.
     */
    public static function resolve(string $marker, int $twoDigitYear): ?int
    {
        if (! array_key_exists($marker, SpecData::FI_CENTURY_MARKERS)) {
            return null;
        }

        return SpecData::FI_CENTURY_MARKERS[$marker] + $twoDigitYear;
    }
}
