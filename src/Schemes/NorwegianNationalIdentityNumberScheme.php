<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\SchemeResult;

final class NorwegianNationalIdentityNumberScheme
{
    // 01-31: the day a D-number would write as 41-71. Disjoint from the
    // D-number's own SHAPE by construction (design doc §4), which is what
    // keeps the two schemes from both matching the same eleven digits -- an
    // unrestricted [0-9]{2} here let a D-number-shaped value match this
    // shape too, and its own date failure (typically ImpossibleDate, since
    // a written day of 41+ is never a real calendar day) could then win the
    // dispatcher's rank-3 tie against the D-number's own, correct answer
    // purely by array insertion order. See CountryDispatchTest's regression
    // test.
    private const string SHAPE = '/^(?<day>0[1-9]|[12][0-9]|3[01])(?<month>[0-9]{2})(?<year>[0-9]{2})'
        . '(?<individual>[0-9]{3})(?<check>[0-9]{2})$/';

    // A fødselsnummer's day is written as the real day, unlike a D-number's +40.
    private const int FODSELSNUMMER_DAY_OFFSET = 0;

    /**
     * Skatteetaten's fødselsnummer. No allowCoordinationNumber or
     * allowUnknownBirthNumber parameters -- Norway has neither convention, so
     * the dispatcher passes this scheme only what it uses.
     */
    public static function parse(
        string $normalized,
        ?DateTimeImmutable $referenceDate,
        bool $allowSyntheticNumbers,
    ): SchemeResult|ParseFailure {
        $matches = preg_match(self::SHAPE, $normalized, $result) === 1 ? $result : null;

        return NorwegianIdentityNumberCore::parse(
            $normalized,
            $matches,
            $referenceDate,
            $allowSyntheticNumbers,
            Scheme::NoNationalIdentityNumber,
            self::FODSELSNUMMER_DAY_OFFSET,
        );
    }
}
