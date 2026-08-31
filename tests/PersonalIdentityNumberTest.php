<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\Format;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Exceptions\ParseException;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PersonalIdentityNumberTest extends TestCase
{
    private const string VALID = '190312049802';

    /**
     * Individual 101, fnr-499-000 series, from
     * spec/sources/norway-synthetic/generated.csv -- the written month is 84,
     * which decodes to April under the Tenor +80 convention. Same value
     * NorwegianNationalIdentityNumberSchemeTest calls SYNTHETIC_MALE.
     */
    private const string SYNTHETIC_NORWEGIAN = '05847510147';

    /** MedCom reserved serial, resolving to 1948-12-25. */
    private const string MEDCOM_DANISH = '251248-9996';

    #[Test]
    public function itDerivesTheBirthDate(): void
    {
        $this->assertSame('1903-12-04', $this->parse()->birthDate()?->format('Y-m-d'));
    }

    #[Test]
    public function itDerivesGenderFromTheSecondToLastDigit(): void
    {
        $this->assertSame(Gender::Female, $this->parse()->gender());
    }

    #[Test]
    public function itComputesAgeAgainstAnExplicitReferenceDate(): void
    {
        $this->assertSame(122, $this->parse()->ageOn(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'))));
    }

    #[Test]
    public function itRendersTheDisplayFormat(): void
    {
        $this->assertSame('19031204-9802', $this->parse()->format(Format::Display));
    }

    #[Test]
    public function itRendersTheShortFormatWithAPlusWhenOverAHundred(): void
    {
        $this->assertSame('031204+9802', $this->parse()->format(Format::Short));
    }

    #[Test]
    public function itMasksTheFinalFourCharacters(): void
    {
        $this->assertSame('19031204****', $this->parse()->format(Format::Masked));
    }

    #[Test]
    public function itKeepsTheRawValueOutOfDebugOutput(): void
    {
        $this->assertStringNotContainsString('9802', print_r($this->parse(), true));
    }

    #[Test]
    public function parseForPersonAcceptsAPersonalNumber(): void
    {
        $number = PersonalIdentityNumber::parseForPerson(
            self::VALID,
            Country::Sweden,
            new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')),
        );

        $this->assertTrue($number->isPerson());
    }

    #[Test]
    public function shortFormatThrowsWithoutAReferenceDate(): void
    {
        $number = PersonalIdentityNumber::parse(
            '19031204-9802',
            Country::Sweden,
            ParseOptions::forCenturyCompleteInput(),
        );

        $this->expectException(ParseException::class);

        $number->format(Format::Short);
    }

    #[Test]
    public function shortFormatAcceptsAReferenceDateOverrideAtTheCallSite(): void
    {
        $number = PersonalIdentityNumber::parse(
            '19031204-9802',
            Country::Sweden,
            ParseOptions::forCenturyCompleteInput(),
        );

        try {
            $number->format(Format::Short);
            $this->fail('Expected ParseException::class to be thrown.');
        } catch (ParseException $exception) {
            $this->assertSame(ParseFailure::ReferenceDateRequired, $exception->getFailure());
        }

        $this->assertSame(
            '031204-9802',
            $number->format(Format::Short, new DateTimeImmutable('2003-12-03', new DateTimeZone('UTC'))),
        );
    }

    /**
     * Regression for the Short-format bug: the over-100 rule must be
     * "has turned 100" (ageOn >= 100), not a raw year subtraction. Rather
     * than a constructed number, this varies the reference date around the
     * published 19031204-9802's own hundredth birthday (2003-12-04), which
     * exercises both sides of the boundary without inventing a new number
     * (see docs/open-threads.md §3.13).
     */
    #[Test]
    public function shortFormatRoundTripsTheDayBeforeTurningOneHundred(): void
    {
        $referenceDate = new DateTimeImmutable('2003-12-03', new DateTimeZone('UTC'));
        $number = PersonalIdentityNumber::parse(
            self::VALID,
            Country::Sweden,
            new ParseOptions($referenceDate),
        );

        $this->assertSame(99, $number->ageOn($referenceDate));

        $short = $number->format(Format::Short);
        $this->assertSame('031204-9802', $short);

        $reparsed = PersonalIdentityNumber::parse($short, Country::Sweden, new ParseOptions($referenceDate));
        $this->assertSame(self::VALID, $reparsed->canonical());
    }

    #[Test]
    public function shortFormatRoundTripsOnTheDayOfTurningOneHundred(): void
    {
        $referenceDate = new DateTimeImmutable('2003-12-04', new DateTimeZone('UTC'));
        $number = PersonalIdentityNumber::parse(
            self::VALID,
            Country::Sweden,
            new ParseOptions($referenceDate),
        );

        $this->assertSame(100, $number->ageOn($referenceDate));

        $short = $number->format(Format::Short);
        $this->assertSame('031204+9802', $short);

        $reparsed = PersonalIdentityNumber::parse($short, Country::Sweden, new ParseOptions($referenceDate));
        $this->assertSame(self::VALID, $reparsed->canonical());
    }

    /**
     * Regression: ageOn() must normalize the reference date to UTC before
     * comparing calendar days, not read whatever timezone the caller
     * attached to it. TypeScript's `Date` has no timezone concept and
     * always reads the UTC calendar day of an instant; a PHP reference date
     * read in its own attached timezone could therefore resolve the exact
     * same instant to a different calendar day — and a different age — than
     * the paired TypeScript runtime. See the mirrored test in
     * personalIdentityNumber.test.ts.
     *
     * The published 19031204-9802 turns 100 on 2003-12-04. This reference
     * instant is 2003-12-04 00:30 in Stockholm's winter offset (+01:00) —
     * already past midnight locally — but only 2003-12-03 23:30 in UTC, a
     * calendar day earlier. Reading the attached Stockholm timezone would
     * report age 100; normalizing to UTC first must report 99, the age the
     * identical instant reads as everywhere else in this package.
     */
    #[Test]
    public function ageOnNormalizesTheReferenceDatesTimezoneToUtc(): void
    {
        $referenceDate = new DateTimeImmutable('2003-12-04 00:30:00', new DateTimeZone('Europe/Stockholm'));
        $number = PersonalIdentityNumber::parse(
            self::VALID,
            Country::Sweden,
            new ParseOptions($referenceDate),
        );

        $this->assertSame(99, $number->ageOn($referenceDate));
    }

    #[Test]
    public function detectReturnsTheSingleSwedishCandidateForAPublishedNumber(): void
    {
        $candidates = PersonalIdentityNumber::detect(
            self::VALID,
            new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'))),
        );

        $this->assertCount(1, $candidates);
        $this->assertSame(Country::Sweden, $candidates[0]->country());
    }

    #[Test]
    public function parseForOrganizationRejectsAPersonalNumber(): void
    {
        try {
            PersonalIdentityNumber::parseForOrganization(
                self::VALID,
                Country::Sweden,
                new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')),
            );
            $this->fail('Expected ParseException::class to be thrown.');
        } catch (ParseException $exception) {
            $this->assertSame(ParseFailure::SchemeNotAllowed, $exception->getFailure());
        }
    }

    #[Test]
    public function equalsIsTrueForTheSameCanonicalValueAndCountry(): void
    {
        $options = new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')));
        $first = PersonalIdentityNumber::parse(self::VALID, Country::Sweden, $options);
        $second = PersonalIdentityNumber::parse(self::VALID, Country::Sweden, $options);

        $this->assertTrue($first->equals($second));
    }

    #[Test]
    public function equalsIsFalseForADifferentCanonicalValue(): void
    {
        $options = new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')));
        $first = PersonalIdentityNumber::parse(self::VALID, Country::Sweden, $options);
        $second = PersonalIdentityNumber::parse('20190101-2391', Country::Sweden, $options);

        $this->assertFalse($first->equals($second));
    }

    #[Test]
    public function reportsWhetherAParsedNumberCameFromATestRange(): void
    {
        $number = PersonalIdentityNumber::parse(
            self::SYNTHETIC_NORWEGIAN,
            Country::Norway,
            new ParseOptions(null, allowSyntheticNumbers: true),
        );

        $this->assertTrue($number->isSynthetic());
    }

    #[Test]
    public function isFalseForANumberFromARealRegistry(): void
    {
        $number = PersonalIdentityNumber::parse(self::MEDCOM_DANISH, Country::Denmark, new ParseOptions(null));

        $this->assertFalse($number->isSynthetic());
    }

    #[Test]
    public function isFalseForASwedishNumberSoTheFieldIsNotAccidentallyNorwayShaped(): void
    {
        $this->assertFalse($this->parse()->isSynthetic());
    }

    private function parse(): PersonalIdentityNumber
    {
        return PersonalIdentityNumber::parse(
            self::VALID,
            Country::Sweden,
            new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'))),
        );
    }
}
