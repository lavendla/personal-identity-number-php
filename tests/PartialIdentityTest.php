<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\Format;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The accessor contract for partial identity, in the two places the fixture
 * schema cannot express it: ageOn() takes an argument, and equals() takes
 * another number.
 *
 * The rule both sides of this test serve: each accessor is null exactly when the
 * digits it reads carry no information. A `0000` birth number therefore keeps a
 * full birth date and an age, and loses only its gender.
 */
final class PartialIdentityTest extends TestCase
{
    /**
     * Unissuable digits, so no bearer can exist: SOU 2008:60 puts the birth
     * number range at 001-999. See spec/fixtures/se/partial-identity.json.
     */
    private const string UNKNOWN_BIRTH_NUMBER = '20240115-0000';

    /**
     * Published Skatteverket coordination numbers with an unknown birth month and
     * an unknown birth day. Both are over a hundred years old at the reference
     * date, which is what makes them the interesting case for the short form.
     */
    private const string UNKNOWN_MONTH = '191500722390';

    private const string UNKNOWN_DAY = '191711602399';

    #[Test]
    public function anUnknownBirthNumberStillReportsAnAge(): void
    {
        $this->assertSame(2, $this->numberFor(self::UNKNOWN_BIRTH_NUMBER)->ageOn($this->referenceDate()));
    }

    /**
     * The identity is complete even when a derived fact is missing, which is the
     * whole argument for accepting these numbers rather than adding a caveat
     * tier. Matching is what consumers do with them, so matching has to work.
     */
    #[Test]
    public function anUnknownBirthNumberIsStillEqualToItself(): void
    {
        $this->assertTrue(
            $this->numberFor(self::UNKNOWN_BIRTH_NUMBER)
                ->equals($this->numberFor('202401150000')),
        );
    }

    #[Test]
    public function aPartialBirthDateHasNoAge(): void
    {
        $this->assertNull($this->numberFor(self::UNKNOWN_MONTH)->ageOn($this->referenceDate()));
    }

    #[Test]
    public function aPartialBirthDateKeepsItsGender(): void
    {
        $this->assertSame(Gender::Male, $this->numberFor(self::UNKNOWN_MONTH)->gender());
    }

    /**
     * The claim §1.4's whole argument rests on: a partial birth date does not make
     * the identity partial, because the canonical form is complete and matching
     * therefore works exactly as it does for any other number. Asserted through
     * two different written forms of the same number so that it is equality being
     * tested rather than string identity.
     */
    #[Test]
    public function aPartialBirthDateIsStillEqualToItself(): void
    {
        $this->assertTrue(
            $this->numberFor(self::UNKNOWN_MONTH)->equals($this->numberFor('19150072-2390')),
        );
    }

    /**
     * The short form has to survive being read back, and for a partial date the
     * +/- separator is the only thing carrying the century. Rendering '-' for a
     * 1915 bearer would re-parse as 2015 — a silent hundred-year error in a value
     * the package itself produced.
     *
     * @return list<array{0: string}>
     */
    public static function partialNumbers(): array
    {
        return [
            [self::UNKNOWN_MONTH],
            [self::UNKNOWN_DAY],
            ['198000602394'],
        ];
    }

    #[Test]
    #[DataProvider('partialNumbers')]
    public function theShortFormOfAPartialNumberReadsBackToTheSameNumber(string $input): void
    {
        $number = $this->numberFor($input);

        $this->assertSame(
            $number->canonical(),
            $this->numberFor($number->format(Format::Short))->canonical(),
        );
    }

    private function numberFor(string $input): PersonalIdentityNumber
    {
        return PersonalIdentityNumber::parse($input, Country::Sweden, new ParseOptions($this->referenceDate()));
    }

    private function referenceDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'));
    }
}
