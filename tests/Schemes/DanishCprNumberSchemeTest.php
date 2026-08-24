<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Schemes;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\SchemeResult;
use Lavendla\PersonalIdentityNumber\Schemes\DanishCprNumberScheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DanishCprNumberSchemeTest extends TestCase
{
    /** MedCom reserved serial 9996, resolving to 1948-12-25. Even serial, so female. */
    private const string MEDCOM_FEMALE = '251248-9996';

    /** MedCom reserved serial 9995, resolving to 1990-04-01. Odd serial, so male. */
    private const string MEDCOM_MALE = '010490-9995';

    #[Test]
    public function acceptsATenDigitNumberWithASeparator(): void
    {
        $this->assertInstanceOf(SchemeResult::class, $this->parse(self::MEDCOM_FEMALE));
    }

    #[Test]
    public function canonicalisesToTenCharactersWithoutASeparator(): void
    {
        $this->assertSame('2512489996', $this->accepted(self::MEDCOM_FEMALE)->canonical);
    }

    #[Test]
    public function resolvesTheSchemeAsCpr(): void
    {
        $this->assertSame(Scheme::DkCprNumber, $this->accepted(self::MEDCOM_FEMALE)->scheme);
    }

    #[Test]
    public function resolvesTheBirthDateThroughTheCenturyTable(): void
    {
        $this->assertSame('1948-12-25', $this->accepted(self::MEDCOM_FEMALE)->birthTime?->toBirthDate()?->iso());
    }

    #[Test]
    public function readsAnEvenSerialAsFemale(): void
    {
        $this->assertSame(Gender::Female, $this->accepted(self::MEDCOM_FEMALE)->gender);
    }

    #[Test]
    public function readsAnOddSerialAsMale(): void
    {
        $this->assertSame(Gender::Male, $this->accepted(self::MEDCOM_MALE)->gender);
    }

    #[Test]
    public function needsNoReferenceDate(): void
    {
        $this->assertInstanceOf(SchemeResult::class, DanishCprNumberScheme::parse('2512489996', null));
    }

    #[Test]
    public function rejectsASerialOfZero(): void
    {
        $this->assertSame(ParseFailure::NotAnIdentityNumber, $this->parse('251248-0000'));
    }

    #[Test]
    public function rejectsADateThatExistsInNoCentury(): void
    {
        $this->assertSame(ParseFailure::ImpossibleDate, $this->parse('300248-3001'));
    }

    /**
     * The resolved century decides whether 29 February exists, and this is the
     * case that proves the table is consulted rather than assumed. Serial 3001
     * resolves to 1900, which is not a leap year because it is divisible by 100
     * and not by 400. The same date with a serial resolving to 2000 would be
     * perfectly valid. An implementation defaulting to the 2000s, or checking
     * the date before resolving the century, accepts this and is wrong.
     */
    #[Test]
    public function theResolvedCenturyDecidesLeapDayValidity(): void
    {
        $this->assertSame(ParseFailure::ImpossibleDate, $this->parse('290200-3000'));
    }

    /**
     * Resolves to 2030, after the reference date. Constructed rather than
     * sourced, and safe to construct precisely because no bearer can exist yet:
     * CPR has not issued numbers for people not yet born.
     */
    #[Test]
    public function rejectsABirthDateAfterTheReferenceDate(): void
    {
        $this->assertSame(ParseFailure::FutureBirthDate, $this->parse('010130-5000'));
    }

    #[Test]
    public function rejectsAShapeThatIsNotTenDigits(): void
    {
        $this->assertSame(ParseFailure::NotAnIdentityNumber, $this->parse('25124899'));
    }

    private function parse(string $input): SchemeResult|ParseFailure
    {
        return DanishCprNumberScheme::parse(
            $input,
            new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')),
        );
    }

    /** Narrows the union so PHPStan permits property access at level max. */
    private function accepted(string $input): SchemeResult
    {
        $result = $this->parse($input);

        $this->assertInstanceOf(SchemeResult::class, $result);

        return $result;
    }
}
