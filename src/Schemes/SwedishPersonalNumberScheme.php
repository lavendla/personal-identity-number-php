<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\BirthTime;
use Lavendla\PersonalIdentityNumber\CenturyResolver;
use Lavendla\PersonalIdentityNumber\Checksum;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use Lavendla\PersonalIdentityNumber\SchemeResult;

final class SwedishPersonalNumberScheme
{
    private const string SHAPE = '/^(?<century>[0-9]{2})?(?<year>[0-9]{2})(?<month>[0-9]{2})(?<day>[0-9]{2})'
        . '(?<separator>[-+])?(?<birthNumber>[0-9]{3})(?<check>[0-9])$/';
    private const int COORDINATION_DAY_OFFSET = 60;

    public static function parse(
        string $normalized,
        ?DateTimeImmutable $referenceDate,
        bool $allowCoordinationNumber = true,
        bool $allowUnknownBirthNumber = true,
    ): SchemeResult|ParseFailure {
        if (preg_match(self::SHAPE, $normalized, $matches) !== 1) {
            return ParseFailure::NotAnIdentityNumber;
        }

        if ($excluded = self::excludedByOptions($matches, $allowCoordinationNumber, $allowUnknownBirthNumber)) {
            return $excluded;
        }

        // An optional group that did not participate is '' here and `undefined` in
        // JavaScript, so the twin file tests the same absence against a different
        // value. Comparing to null or falsiness would diverge between runtimes.
        $hasCentury = $matches['century'] !== '';

        if (! $hasCentury && $referenceDate === null) {
            return ParseFailure::CenturyRequired;
        }

        $year = $hasCentury
            ? (int) ($matches['century'] . $matches['year'])
            : CenturyResolver::resolve(
                (int) $matches['year'],
                self::declaredMonth($matches),
                self::declaredDay($matches),
                $matches['separator'] === '+',
                $referenceDate,
            );

        $birthTime = new BirthTime($year, self::declaredMonth($matches), self::declaredDay($matches));

        return self::dateRefusal($birthTime, $referenceDate)
            ?? self::checksumRefusal($matches)
            ?? self::accept($matches, $birthTime);
    }

    /**
     * Recognition first, exclusion second: reporting SchemeNotAllowed requires
     * having recognised the number, and the caller's own option outranks every
     * other refusal in the precedence table.
     *
     * @param array<int|string, string> $matches
     */
    private static function excludedByOptions(
        array $matches,
        bool $allowCoordinationNumber,
        bool $allowUnknownBirthNumber,
    ): ?ParseFailure {
        $excluded = (self::isCoordinationNumber($matches) && ! $allowCoordinationNumber)
            || (self::declaresUnknownBirthNumber($matches) && ! $allowUnknownBirthNumber);

        return $excluded ? ParseFailure::SchemeNotAllowed : null;
    }

    private static function dateRefusal(BirthTime $birthTime, ?DateTimeImmutable $referenceDate): ?ParseFailure
    {
        if (! $birthTime->isPossible()) {
            return ParseFailure::ImpossibleDate;
        }

        // After the date exists, never before: a date that never occurred is not
        // made plausible by being recent, and reporting the floor for 30 February
        // would be actively misleading. The floor is declared in
        // spec/schemes/se/personal-number.json rather than written here, so the
        // two runtimes cannot drift on it.
        if ($birthTime->year < SpecData::MINIMUM_BIRTH_YEARS['se-personal-number']) {
            return ParseFailure::ImplausibleBirthDate;
        }

        $isFuture = $referenceDate !== null
            && $birthTime->earliestPossibleIso() > $referenceDate->format('Y-m-d');

        return $isFuture ? ParseFailure::FutureBirthDate : null;
    }

    /** @param array<int|string, string> $matches */
    private static function checksumRefusal(array $matches): ?ParseFailure
    {
        if (self::isChecksumExempt($matches)) {
            return null;
        }

        // The century is deliberately absent from the Luhn input. Skatteverket
        // computes the check digit from the birth-time as stored — the offset day
        // included — plus the birth number, and the citation for that lives in
        // spec/schemes/se/personal-number.json.
        $checkable = $matches['year'] . $matches['month'] . $matches['day'] . $matches['birthNumber'];

        return Checksum::luhn($checkable) === (int) $matches['check'] ? null : ParseFailure::ChecksumMismatch;
    }

    /**
     * The unknown-birth-number convention is written as four zeros and there is
     * nothing to verify its check digit against: Skatteverket does not issue a
     * birth number of 000, so it publishes no check-digit rule for one. Luhn over
     * YYMMDD000 yields 0 for roughly one date in ten, so applying the checksum
     * would refuse the convention for most birth dates, which is the
     * contradiction docs/open-threads.md §1.4 exists to resolve.
     *
     * The exemption is this exact four-digit tail and nothing wider — a birth
     * number of 000 with any other check digit still gets the ordinary check.
     *
     * @param array<int|string, string> $matches
     */
    private static function isChecksumExempt(array $matches): bool
    {
        return SpecData::SE_PARTIAL_IDENTITY['checksumExemptTail'] === $matches['birthNumber'] . $matches['check'];
    }

    /** @param array<int|string, string> $matches */
    private static function accept(array $matches, BirthTime $birthTime): SchemeResult
    {
        // The day digits as written go in the canonical form and dayOf() goes in
        // the birth date: a coordination number's +60 offset is part of the number
        // but not part of the birthday. Swapping them diffs every coordination
        // fixture in the golden snapshot, which is how this comment was verified.
        $digitsAfterTheYear = $matches['month'] . $matches['day'] . $matches['birthNumber'] . $matches['check'];

        return new SchemeResult(
            self::isCoordinationNumber($matches) ? Scheme::SeCoordinationNumber : Scheme::SePersonalNumber,
            sprintf('%04d', $birthTime->year) . $digitsAfterTheYear,
            $birthTime,
            self::genderOf($matches),
            false,
        );
    }

    /**
     * Null for an unknown birth number. The parity rule applies to a birth number
     * the registry issued, and 000 is outside the issuable range of 001-999 (SOU
     * 2008:60), so its third digit is not a gender digit and reporting a gender
     * from it would be fabrication. The date beside it is untouched: each
     * accessor is null exactly when the digits it reads carry no information.
     *
     * @param array<int|string, string> $matches
     */
    private static function genderOf(array $matches): ?Gender
    {
        if (self::declaresUnknownBirthNumber($matches)) {
            return null;
        }

        return (int) $matches['birthNumber'][2] % 2 === 1 ? Gender::Male : Gender::Female;
    }

    /** @param array<int|string, string> $matches */
    private static function declaresUnknownBirthNumber(array $matches): bool
    {
        return $matches['birthNumber'] === SpecData::SE_PARTIAL_IDENTITY['unknownBirthNumber'];
    }

    /**
     * At the offset, not past it. A day of exactly 60 is day 00 with the offset
     * applied — an unknown birth day — so `> 60` classified it as an ordinary
     * calendar day of 60 and refused it as an impossible date. 41 of the 1498
     * published coordination numbers in
     * spec/sources/skatteverket/testsamordningsnummer-1914-2019.csv are that
     * shape, and this package rejected every one of them.
     *
     * @param array<int|string, string> $matches
     */
    private static function isCoordinationNumber(array $matches): bool
    {
        return (int) $matches['day'] >= self::COORDINATION_DAY_OFFSET;
    }

    /**
     * Null when the number declares the month unknown, which only a coordination
     * number can do. A month of `00` on an ordinary personal number is not a
     * declaration of anything: no published personnummer uses it — zero rows
     * across all four Skatteverket personal-number files, against 130 of 1498 in
     * the coordination file — so accepting it there would admit a shape the
     * registry does not issue and weaken what validates() means.
     *
     * @param array<int|string, string> $matches
     */
    private static function declaredMonth(array $matches): ?int
    {
        $declaresUnknownMonth = self::isCoordinationNumber($matches)
            && $matches['month'] === SpecData::SE_PARTIAL_IDENTITY['unknownMonth'];

        return $declaresUnknownMonth ? null : (int) $matches['month'];
    }

    /**
     * Null when the number declares the day unknown. Read from the digits as
     * written rather than from the offset-removed day, because `60` is the whole
     * marker: the day is `00` and the offset is part of how a coordination number
     * writes it.
     *
     * @param array<int|string, string> $matches
     */
    private static function declaredDay(array $matches): ?int
    {
        return $matches['day'] === SpecData::SE_PARTIAL_IDENTITY['unknownDayEncoded']
            ? null
            : self::dayOf($matches);
    }

    /** @param array<int|string, string> $matches */
    private static function dayOf(array $matches): int
    {
        $day = (int) $matches['day'];

        return self::isCoordinationNumber($matches) ? $day - self::COORDINATION_DAY_OFFSET : $day;
    }
}
