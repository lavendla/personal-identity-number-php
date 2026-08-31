<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Schemes;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use Lavendla\PersonalIdentityNumber\SchemeResult;
use Lavendla\PersonalIdentityNumber\Schemes\NorwegianDNumberScheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Most D-numbers below come from spec/sources/norway-synthetic/generated.csv,
 * produced by tools/generate-norwegian-synthetic.mjs -- IMPOSSIBLE_DATE_D_NUMBER
 * and FUTURE_D_NUMBER are constructed instead, each with its own docblock
 * saying so. Every value, sourced or constructed, carries both the
 * D-number's +40 day AND Tenor's +80 month convention -- a written month of
 * 81-92, which no bearer can hold -- because the day offset alone does not
 * make a value safe under CLAUDE.md's Exception 1: a D-number with an
 * ordinary written month is a real identity number Folkeregisteret could
 * have issued to a real non-resident. The synthetic month is what makes it
 * structurally incapable of having a bearer. No hand-picked "ordinary"
 * D-number appears anywhere in this file for that reason.
 */
final class NorwegianDNumberSchemeTest extends TestCase
{
    /** Individual 151, dnr-000-499 series. Written day 41 decodes to the 1st, written month 83 to March. */
    private const string SYNTHETIC_D_NUMBER = '41836515143';

    /** Individual 300, dnr-000-499 series, resolving to 1999-07-31. */
    private const string SYNTHETIC_FEMALE = '71879930051';

    /**
     * Individual 843, dnr-500-999 series, year 23 -> 2023-11-25. An
     * ordinary post-2000 D-number -- year 23 is inside §2-2-1's own
     * 39-year cap too, so this value alone cannot distinguish the
     * D-number table from fødselsnummer's. FUTURE_D_NUMBER (year 99) is
     * what actually exercises the D-number table's lack of a year cap.
     */
    private const string SYNTHETIC_2000S_SERIES = '65912384300';

    /**
     * A fødselsnummer (day written 01-31, not 41-71) borrowed from
     * NorwegianNationalIdentityNumberSchemeTest's SYNTHETIC_MALE.
     * Demonstrates that the D-number shape refuses it outright rather than
     * accepting it and later failing the date check.
     */
    private const string FODSELSNUMMER = '05847510147';

    /**
     * Day 71 (-> 31), written month 84 (-> April, 30 days), individual 151,
     * year 65: no April 31st. Constructed rather than sourced -- checksum
     * verified by hand against NorwegianChecksum's own weight vectors -- and
     * safe under Exception 1 for the same reason as every other value in
     * this file: the written month is 81-92, so no bearer can exist
     * regardless of what the day resolves to.
     */
    private const string IMPOSSIBLE_DATE_D_NUMBER = '71846515156';

    /**
     * Day 65 (-> 25), written month 89 (-> September), individual 843
     * (dnr-500-999, no year cap), year 99 -> 2099. Demonstrates
     * d-number.json's centuryTable.description point directly: a
     * far-future two-digit year is not excluded by the table itself -- it
     * is caught by the ordinary future-birth-date check instead.
     */
    private const string FUTURE_D_NUMBER = '65899984395';

    private const string REFERENCE_DATE = '2026-08-16';

    #[Test]
    public function subtractsFortyFromTheDayToReachTheBirthDate(): void
    {
        $this->assertSame(
            (int) substr(self::SYNTHETIC_D_NUMBER, 0, 2) - SpecData::NO_DAY_OFFSET,
            $this->accepted(self::SYNTHETIC_D_NUMBER, true)->birthTime?->day,
        );
    }

    /**
     * The registry issued these eleven digits and an application indexes on
     * them. Also proves the checksum runs on the digits as written, before
     * either offset is removed: Skatteetaten computes a D-number's check
     * digits over the +40 day, so stripping it before checksumming would run
     * modulus-11 over digits the registry never signed.
     */
    #[Test]
    public function keepsTheOffsetDayInTheCanonicalForm(): void
    {
        $this->assertSame(self::SYNTHETIC_D_NUMBER, $this->accepted(self::SYNTHETIC_D_NUMBER, true)->canonical);
    }

    #[Test]
    public function refusesADayOutsideTheDNumberRange(): void
    {
        $this->assertSame(ParseFailure::NotAnIdentityNumber, $this->parse(self::FODSELSNUMMER, true, null));
    }

    #[Test]
    public function refusesASyntheticNumberWhenTheFlagIsOff(): void
    {
        $this->assertSame(ParseFailure::SyntheticNumber, $this->parse(self::SYNTHETIC_D_NUMBER, false));
    }

    #[Test]
    public function parsesTheSameNumberWhenTheFlagIsOn(): void
    {
        $this->assertInstanceOf(SchemeResult::class, $this->parse(self::SYNTHETIC_D_NUMBER, true));
    }

    #[Test]
    public function resolvesTheSchemeAsTheNorwegianDNumber(): void
    {
        $this->assertSame(Scheme::NoDNumber, $this->accepted(self::SYNTHETIC_D_NUMBER, true)->scheme);
    }

    #[Test]
    public function marksAnAcceptedSyntheticDNumberAsSynthetic(): void
    {
        $this->assertTrue($this->accepted(self::SYNTHETIC_D_NUMBER, true)->synthetic);
    }

    #[Test]
    public function resolvesTheBirthDateThroughTheDNumberCenturyTableWithBothOffsetsRemoved(): void
    {
        $this->assertSame(
            '1999-07-31',
            $this->accepted(self::SYNTHETIC_FEMALE, true)->birthTime?->toBirthDate()?->iso(),
        );
    }

    #[Test]
    public function resolvesIndividnummer500To999ToThe2000sSeries(): void
    {
        $this->assertSame(
            '2023-11-25',
            $this->accepted(self::SYNTHETIC_2000S_SERIES, true)->birthTime?->toBirthDate()?->iso(),
        );
    }

    #[Test]
    public function readsAnOddIndividualDigitAsMale(): void
    {
        $this->assertSame(Gender::Male, $this->accepted(self::SYNTHETIC_D_NUMBER, true)->gender);
    }

    #[Test]
    public function readsAnEvenIndividualDigitAsFemale(): void
    {
        $this->assertSame(Gender::Female, $this->accepted(self::SYNTHETIC_FEMALE, true)->gender);
    }

    #[Test]
    public function rejectsAChecksumMismatch(): void
    {
        $mutated = substr(self::SYNTHETIC_D_NUMBER, 0, -1) . '8';

        $this->assertSame(ParseFailure::ChecksumMismatch, $this->parse($mutated, true));
    }

    #[Test]
    public function rejectsAShapeThatIsNotElevenDigits(): void
    {
        $this->assertSame(
            ParseFailure::NotAnIdentityNumber,
            $this->parse(substr(self::SYNTHETIC_D_NUMBER, 0, -1), true),
        );
    }

    #[Test]
    public function rejectsABirthDateAfterTheReferenceDateUsingTheFarFutureYearTheCenturyTableDoesNotCap(): void
    {
        $this->assertSame(ParseFailure::FutureBirthDate, $this->parse(self::FUTURE_D_NUMBER, true));
    }

    #[Test]
    public function rejectsADateThatDoesNotExist(): void
    {
        $this->assertSame(ParseFailure::ImpossibleDate, $this->parse(self::IMPOSSIBLE_DATE_D_NUMBER, true));
    }

    private function parse(
        string $input,
        bool $allowSynthetic,
        ?string $referenceDate = self::REFERENCE_DATE,
    ): SchemeResult|ParseFailure {
        return NorwegianDNumberScheme::parse(
            $input,
            $referenceDate === null ? null : new DateTimeImmutable($referenceDate, new DateTimeZone('UTC')),
            $allowSynthetic,
        );
    }

    private function accepted(
        string $input,
        bool $allowSynthetic,
        ?string $referenceDate = self::REFERENCE_DATE,
    ): SchemeResult {
        $result = $this->parse($input, $allowSynthetic, $referenceDate);

        if (! $result instanceof SchemeResult) {
            $this->fail('expected a SchemeResult');
        }

        return $result;
    }
}
