<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Schemes;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\SchemeResult;
use Lavendla\PersonalIdentityNumber\Schemes\NorwegianNationalIdentityNumberScheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every value below comes from spec/sources/norway-synthetic/generated.csv,
 * produced by tools/generate-norwegian-synthetic.mjs. Every row in that file
 * carries the Tenor +80 month convention -- a written month of 81-92, which no
 * bearer can hold -- so it is safe under CLAUDE.md's Exception 1: constructing
 * a personal identity number is normally forbidden because a valid one very
 * likely belongs to a living person, but these are structurally incapable of
 * being issued to anyone. No hand-picked "ordinary" Norwegian number appears
 * anywhere in this file for that reason.
 */
final class NorwegianNationalIdentityNumberSchemeTest extends TestCase
{
    /** Individual 101, fnr-499-000 series. Written month 84 decodes to April. */
    private const string SYNTHETIC_MALE = '05847510147';

    /** Individual 250, fnr-499-000 series. Written month 91 decodes to November. */
    private const string SYNTHETIC_FEMALE = '18918825033';

    /** Individual 777, fnr-999-500 series, resolving to 2024-02-29. */
    private const string SYNTHETIC_FUTURE_LEAP_DAY = '29822477753';

    /**
     * Day 31, written month 84 (decodes to April, which has 30 days),
     * individual 101, year 75. Constructed rather than sourced: checksum
     * verified by hand against NorwegianChecksum's own weight vectors, and
     * safe under Exception 1 for the same reason as every other value in this
     * file -- the written month is 81-92, so no bearer can exist regardless of
     * what the day resolves to.
     */
    private const string IMPOSSIBLE_DATE_SYNTHETIC = '31847510113';

    private const string REFERENCE_DATE = '2026-08-16';

    #[Test]
    public function refusesASyntheticNumberWhenTheFlagIsOff(): void
    {
        $this->assertSame(ParseFailure::SyntheticNumber, $this->parse(self::SYNTHETIC_MALE, false));
    }

    #[Test]
    public function parsesTheSameNumberWhenTheFlagIsOn(): void
    {
        $this->assertInstanceOf(SchemeResult::class, $this->parse(self::SYNTHETIC_MALE, true));
    }

    /**
     * The guard the design calls the most likely implementation error:
     * Skatteetaten computes a synthetic number's check digits AFTER adding 80
     * to the month, so stripping the offset before checksumming runs
     * modulus-11 over digits the registry never signed and every synthetic
     * number would fail. Do not delete.
     */
    #[Test]
    public function checksumsTheDigitsAsWrittenBeforeAnyOffsetIsRemoved(): void
    {
        $this->assertSame(self::SYNTHETIC_MALE, $this->accepted(self::SYNTHETIC_MALE, true)->canonical);
    }

    #[Test]
    public function resolvesTheSchemeAsTheNorwegianNationalIdentityNumber(): void
    {
        $this->assertSame(
            Scheme::NoNationalIdentityNumber,
            $this->accepted(self::SYNTHETIC_MALE, true)->scheme,
        );
    }

    #[Test]
    public function marksAnAcceptedSyntheticNumberAsSynthetic(): void
    {
        $this->assertTrue($this->accepted(self::SYNTHETIC_MALE, true)->synthetic);
    }

    #[Test]
    public function resolvesTheBirthDateThroughTheCenturyTableWithTheOffsetRemoved(): void
    {
        $this->assertSame(
            '1988-11-18',
            $this->accepted(self::SYNTHETIC_FEMALE, true)->birthTime?->toBirthDate()?->iso(),
        );
    }

    #[Test]
    public function readsAnOddIndividualDigitAsMale(): void
    {
        $this->assertSame(Gender::Male, $this->accepted(self::SYNTHETIC_MALE, true)->gender);
    }

    #[Test]
    public function readsAnEvenIndividualDigitAsFemale(): void
    {
        $this->assertSame(Gender::Female, $this->accepted(self::SYNTHETIC_FEMALE, true)->gender);
    }

    #[Test]
    public function rejectsAChecksumMismatch(): void
    {
        $mutated = substr(self::SYNTHETIC_MALE, 0, -1) . '8';

        $this->assertSame(ParseFailure::ChecksumMismatch, $this->parse($mutated, true));
    }

    #[Test]
    public function rejectsAShapeThatIsNotElevenDigits(): void
    {
        $this->assertSame(
            ParseFailure::NotAnIdentityNumber,
            $this->parse(substr(self::SYNTHETIC_MALE, 0, -1), true),
        );
    }

    #[Test]
    public function rejectsABirthDateAfterTheReferenceDate(): void
    {
        $this->assertSame(
            ParseFailure::FutureBirthDate,
            $this->parse(self::SYNTHETIC_FUTURE_LEAP_DAY, true, '2020-01-01'),
        );
    }

    #[Test]
    public function rejectsADateThatDoesNotExist(): void
    {
        $this->assertSame(ParseFailure::ImpossibleDate, $this->parse(self::IMPOSSIBLE_DATE_SYNTHETIC, true));
    }

    private function parse(
        string $input,
        bool $allowSynthetic,
        ?string $referenceDate = self::REFERENCE_DATE,
    ): SchemeResult|ParseFailure {
        return NorwegianNationalIdentityNumberScheme::parse(
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
