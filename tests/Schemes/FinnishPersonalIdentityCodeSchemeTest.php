<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Schemes;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\SchemeResult;
use Lavendla\PersonalIdentityNumber\Schemes\FinnishPersonalIdentityCodeScheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every code below that must be **valid** is a DVV-published test identity from
 * spec/sources/dvv/test-identities.csv, safe under CLAUDE.md's Exception 3: its
 * individual number is in 900-999, the block DVV states it does not issue from,
 * so the digits are in no population register and identify nobody. Codes that
 * must be **invalid** are those same identities with one character changed, so
 * they fail the modulus-31 control character or carry a date that cannot exist
 * -- and no bearer can hold either.
 *
 * No code here is both constructed and valid. Exception 3 does not stretch to
 * cover one: hospitals do assign 900-999 codes to real patients, so "no bearer
 * can exist" is false for a code DVV did not publish.
 */
final class FinnishPersonalIdentityCodeSchemeTest extends TestCase
{
    /** Individual 952, last digit even, so female. Marker '-' reads as 1900s. */
    private const string PUBLISHED_FEMALE = '010280-952L';

    /** Individual 999, last digit odd, so male. */
    private const string PUBLISHED_MALE = '010469-999W';

    /** Marker X, one of the letters decree 690/2022 added for 1900s births in 2023. */
    private const string PUBLISHED_X_MARKER = '020594X903P';

    /** Marker B, added by the same decree for 2000s births. */
    private const string PUBLISHED_B_MARKER = '010516B903X';

    private const string REFERENCE_DATE = '2026-08-16';

    #[Test]
    public function itParsesAPublishedDvvTestIdentity(): void
    {
        $this->assertSame('1980-02-01', $this->accepted(self::PUBLISHED_FEMALE)->birthTime?->toBirthDate()?->iso());
    }

    #[Test]
    public function itReadsTheCenturyFromAMarkerAddedIn2023(): void
    {
        $this->assertSame('1994-05-02', $this->accepted(self::PUBLISHED_X_MARKER)->birthTime?->toBirthDate()?->iso());
    }

    #[Test]
    public function itReadsATwoThousandsMarkerAsATwoThousandsBirth(): void
    {
        $this->assertSame('2016-05-01', $this->accepted(self::PUBLISHED_B_MARKER)->birthTime?->toBirthDate()?->iso());
    }

    /**
     * Decree 690/2022 means a Finnish code is not unique without its
     * intermediate character, so it can never be discarded the way a Swedish
     * separator can.
     */
    #[Test]
    public function itKeepsTheMarkerInTheCanonicalForm(): void
    {
        $this->assertSame(self::PUBLISHED_FEMALE, $this->accepted(self::PUBLISHED_FEMALE)->canonical);
    }

    #[Test]
    public function itReportsTheSchemeItAcceptedTheValueUnder(): void
    {
        $this->assertSame(Scheme::FiPersonalIdentityCode, $this->accepted(self::PUBLISHED_FEMALE)->scheme);
    }

    #[Test]
    public function itReadsAnEvenLastIndividualDigitAsFemale(): void
    {
        $this->assertSame(Gender::Female, $this->accepted(self::PUBLISHED_FEMALE)->gender);
    }

    #[Test]
    public function itReadsAnOddLastIndividualDigitAsMale(): void
    {
        $this->assertSame(Gender::Male, $this->accepted(self::PUBLISHED_MALE)->gender);
    }

    /**
     * DVV's 002-899 is an observation about issuance, not a stated validity
     * rule, and this package enforces only stated rules -- see the Danish
     * precedent, where modulus-11 is not a validity rule either.
     */
    #[Test]
    public function itAcceptsAnIndividualNumberInTheTemporaryBlock(): void
    {
        $this->assertInstanceOf(SchemeResult::class, $this->parse(self::PUBLISHED_MALE));
    }

    /**
     * Finland publishes no test-data convention of Skatteetaten's kind, so
     * there is nothing for allowSyntheticNumbers to gate here. Set explicitly
     * rather than defaulted, so a scheme that forgets the field fails to
     * compile.
     */
    #[Test]
    public function itNeverReportsAFinnishCodeAsSynthetic(): void
    {
        $this->assertFalse($this->accepted(self::PUBLISHED_FEMALE)->synthetic);
    }

    /** PUBLISHED_FEMALE with M where the alphabet indexes L. */
    #[Test]
    public function itRejectsACodeWhoseControlCharacterDoesNotMatch(): void
    {
        $this->assertSame(ParseFailure::ChecksumMismatch, $this->parse('010280-952M'));
    }

    /**
     * 'H' is in spec/characters.json's allow table -- Finland's own control
     * alphabet contains it -- so it reaches this scheme rather than being
     * refused as an invalid character. It is not a century marker, and the
     * control character is still L, because the marker is not part of the
     * checksummed digits: so this reaches the century lookup and fails there.
     */
    #[Test]
    public function itRejectsACharacterTheDecreeNeverAssignedToACentury(): void
    {
        $this->assertSame(ParseFailure::NotAnIdentityNumber, $this->parse('010280H952L'));
    }

    #[Test]
    public function itRejectsAValueThatIsNotElevenCharacters(): void
    {
        $this->assertSame(ParseFailure::NotAnIdentityNumber, $this->parse('010280-952'));
    }

    /**
     * 30 February 1980. Control character A, so it clears the checksum and
     * fails on the date -- which is the only order in which this branch is
     * reachable.
     */
    #[Test]
    public function itRejectsADateThatCannotExist(): void
    {
        $this->assertSame(ParseFailure::ImpossibleDate, $this->parse('300280-952A'));
    }

    /**
     * 1900 was not a leap year. The same six digits with a 2000s marker would
     * be a real date, which is why the century is resolved before the date is
     * checked rather than after.
     */
    #[Test]
    public function itRejectsTheTwentyNinthOfFebruaryInANonLeapCenturyYear(): void
    {
        $this->assertSame(ParseFailure::ImpossibleDate, $this->parse('290200-9521'));
    }

    #[Test]
    public function itRejectsABirthDateAfterTheReferenceDate(): void
    {
        $this->assertSame(ParseFailure::FutureBirthDate, $this->parse(self::PUBLISHED_FEMALE, '1979-01-01'));
    }

    /**
     * The boundary the check above is one day past. Chosen by moving the
     * reference date to the birth date rather than by hunting for a code.
     */
    #[Test]
    public function itAcceptsABirthDateOnTheReferenceDateItself(): void
    {
        $this->assertSame(
            '1980-02-01',
            $this->accepted(self::PUBLISHED_FEMALE, '1980-02-01')->birthTime?->toBirthDate()?->iso(),
        );
    }

    #[Test]
    public function itDoesNotCheckTheFutureWhenNoReferenceDateIsGiven(): void
    {
        $this->assertSame(2016, $this->accepted(self::PUBLISHED_B_MARKER, null)->birthTime?->year);
    }

    private function parse(
        string $input,
        ?string $referenceDate = self::REFERENCE_DATE,
    ): SchemeResult|ParseFailure {
        return FinnishPersonalIdentityCodeScheme::parse(
            $input,
            $referenceDate === null ? null : new DateTimeImmutable($referenceDate, new DateTimeZone('UTC')),
        );
    }

    private function accepted(string $input, ?string $referenceDate = self::REFERENCE_DATE): SchemeResult
    {
        $result = $this->parse($input, $referenceDate);

        if (! $result instanceof SchemeResult) {
            $this->fail('expected a SchemeResult');
        }

        return $result;
    }
}
