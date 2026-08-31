<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use Lavendla\PersonalIdentityNumber\SchemeResult;

final class NorwegianDNumberScheme
{
    // 41-71: 4[1-9] covers 41-49, [56][0-9] covers 50-69, 7[01] covers 70-71
    // -- the day a fødselsnummer would write as 01-31, plus the day offset.
    // Encoded here, at shape level, rather than left to isRealDate() after
    // subtracting the offset, because a written day outside 41-71 is not a
    // D-number at all (NotAnIdentityNumber), whereas a written day inside
    // that range that still decodes to no real date is a D-number with an
    // impossible birth date (ImpossibleDate) -- two different failures the
    // shape alone must tell apart.
    private const string SHAPE = '/^(?<day>4[1-9]|[56][0-9]|7[01])(?<month>[0-9]{2})(?<year>[0-9]{2})'
        . '(?<individual>[0-9]{3})(?<check>[0-9]{2})$/';

    /**
     * Skatteetaten's D-number, issued to people not resident in Norway.
     * Folkeregisterhåndboken § 2-2-2: "D-nummer er bygget opp på samme måte,
     * likevel slik at første siffer er tillagt fire" -- built up the same way
     * as a fødselsnummer, except the day carries +40. See
     * NorwegianIdentityNumberCore for the body shared with that scheme.
     *
     * canonical() keeps the +40 day: that is what the registry issued and
     * what an application indexes on, exactly as a synthetic number's
     * canonical form keeps its +80 month.
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
            Scheme::NoDNumber,
            SpecData::NO_DAY_OFFSET,
        );
    }
}
