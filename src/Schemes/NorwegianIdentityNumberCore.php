<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\BirthTime;
use Lavendla\PersonalIdentityNumber\DateValidator;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use Lavendla\PersonalIdentityNumber\NorwegianCenturyResolver;
use Lavendla\PersonalIdentityNumber\NorwegianChecksum;
use Lavendla\PersonalIdentityNumber\SchemeResult;

/**
 * Shared by the fødselsnummer and D-number schemes, which differ only in the
 * day's written range, the offset subtracted from it, and which scheme
 * member and century table apply -- everything else, including the
 * checksum, the synthetic gate and the date checks, is identical. Extracted
 * here rather than duplicated per CLAUDE.md's rule against copied code.
 *
 * Takes already-matched `$matches` rather than a shape to match itself --
 * each caller runs its own SHAPE regex against `$normalized` before calling
 * in, because only it knows its day's written range (01-31 for a
 * fødselsnummer, 41-71 for a D-number). Parameter order mirrors
 * parseNorwegianIdentityNumber() in the TypeScript twin exactly, so the two
 * surfaces stay comparable by eye.
 */
final class NorwegianIdentityNumberCore
{
    /**
     * @param array<int|string, string>|null $matches
     */
    public static function parse(
        string $normalized,
        ?array $matches,
        ?DateTimeImmutable $referenceDate,
        bool $allowSyntheticNumbers,
        Scheme $scheme,
        int $dayOffset,
    ): SchemeResult|ParseFailure {
        if ($matches === null) {
            return ParseFailure::NotAnIdentityNumber;
        }

        // Before any offset is removed, deliberately. Skatteetaten computes a
        // synthetic number's check digits AFTER adding 80 to the month, and
        // the registry computes a D-number's over the +40 day -- the
        // checksummed value is always the value as written, never the decoded
        // one. See spec/schemes/no/national-identity-number.json's
        // checkDigitWeights note.
        if (! NorwegianChecksum::matches($normalized)) {
            return ParseFailure::ChecksumMismatch;
        }

        $writtenMonth = (int) $matches['month'];

        // Upper bound derived from the offset rather than hardcoded to 92: the
        // +80 marker only ever encodes a real calendar month, and a year has
        // 12 of those, so the highest writable synthetic month is the offset
        // plus 12. A written month past that (93-99) is not a +80 encoding of
        // anything and must fall through to the ordinary date check instead
        // of being waved through as Skatteetaten's test-data marker.
        $synthetic = $writtenMonth > SpecData::NO_SYNTHETIC_MONTH_OFFSET
            && $writtenMonth <= SpecData::NO_SYNTHETIC_MONTH_OFFSET + 12;

        if ($synthetic && ! $allowSyntheticNumbers) {
            return ParseFailure::SyntheticNumber;
        }

        $month = $synthetic ? $writtenMonth - SpecData::NO_SYNTHETIC_MONTH_OFFSET : $writtenMonth;
        $day = (int) $matches['day'] - $dayOffset;
        $year = NorwegianCenturyResolver::resolve(
            $scheme->value,
            (int) $matches['individual'],
            (int) $matches['year'],
        );

        // No row matched, which means the registry cannot have issued this
        // individnummer/year combination at all -- not that the resulting
        // date is impossible. See NorwegianCenturyResolver's own doc comment.
        if ($year === null) {
            return ParseFailure::NotAnIdentityNumber;
        }

        if (! DateValidator::isRealDate($year, $month, $day)) {
            return ParseFailure::ImpossibleDate;
        }

        if ($referenceDate !== null && DateValidator::iso($year, $month, $day) > $referenceDate->format('Y-m-d')) {
            return ParseFailure::FutureBirthDate;
        }

        return new SchemeResult(
            $scheme,
            $normalized,
            new BirthTime($year, $month, $day),
            (int) $matches['individual'][2] % 2 === 1 ? Gender::Male : Gender::Female,
            $synthetic,
        );
    }
}
