<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\BirthTime;
use Lavendla\PersonalIdentityNumber\DateValidator;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\FinnishCenturyResolver;
use Lavendla\PersonalIdentityNumber\FinnishChecksum;
use Lavendla\PersonalIdentityNumber\SchemeResult;

final class FinnishPersonalIdentityCodeScheme
{
    /**
     * Positions only. Which characters position 7 and position 11 may hold is
     * scheme data, not shape: the marker is validated by the century map, which
     * is generated from decree 690/2022, and the control character by the
     * modulus-31 alphabet. Spelling either set out here as well would be a
     * third copy of data that already exists once in spec/ -- and the copies
     * would drift, leaving a marker the decree assigns that this pattern
     * rejects, or the reverse.
     *
     * `[^0-9]` is safe as the outer bound because the normalizer has already
     * refused everything outside spec/characters.json's allow table, so the
     * only non-digits that can reach here are `-`, `+` and the uppercase
     * letters Finland's own alphabet contributed.
     */
    private const string SHAPE = '/^(?<day>[0-9]{2})(?<month>[0-9]{2})(?<year>[0-9]{2})'
        . '(?<marker>[^0-9])(?<individual>[0-9]{3})(?<control>[0-9A-Z])$/';

    /**
     * DVV's personal identity code. No allowSyntheticNumbers parameter: Finland
     * publishes no test-data convention of Skatteetaten's kind, so there is
     * nothing to gate -- the dispatcher passes this scheme only what it uses.
     *
     * The individual number is not range-checked. DVV's "in practice, all
     * individual numbers issued are between 002 and 899" is an observation
     * about issuance, not a stated validity rule, and this package enforces
     * only stated rules -- the same reason modulus-11 is not a Danish validity
     * rule. Which is also what makes the corpus possible: every fixture
     * asserting a valid Finnish code draws from the 900-999 block precisely
     * because DVV does not issue there.
     */
    public static function parse(string $normalized, ?DateTimeImmutable $referenceDate): SchemeResult|ParseFailure
    {
        if (preg_match(self::SHAPE, $normalized, $matches) !== 1) {
            return ParseFailure::NotAnIdentityNumber;
        }

        if (! FinnishChecksum::matches($normalized)) {
            return ParseFailure::ChecksumMismatch;
        }

        $year = FinnishCenturyResolver::resolve($matches['marker'], (int) $matches['year']);

        // A character the decree assigned to no century, so the value is not a
        // Finnish code at all -- not a code whose date is wrong.
        if ($year === null) {
            return ParseFailure::NotAnIdentityNumber;
        }

        $month = (int) $matches['month'];
        $day = (int) $matches['day'];

        // After the century is resolved, never before: whether 29 February
        // exists depends on which century the marker named. 1900 was not a leap
        // year and 2000 was.
        if (! DateValidator::isRealDate($year, $month, $day)) {
            return ParseFailure::ImpossibleDate;
        }

        if ($referenceDate !== null && DateValidator::iso($year, $month, $day) > $referenceDate->format('Y-m-d')) {
            return ParseFailure::FutureBirthDate;
        }

        return new SchemeResult(
            Scheme::FiPersonalIdentityCode,
            // The eleven characters as written, marker included. Decree
            // 690/2022 made the marker load-bearing: a code is not unique
            // without it, so dropping it from the value applications index on
            // would merge two people.
            $normalized,
            new BirthTime($year, $month, $day),
            (int) $matches['individual'][2] % 2 === 1 ? Gender::Male : Gender::Female,
            false,
        );
    }
}
