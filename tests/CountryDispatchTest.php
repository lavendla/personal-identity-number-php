<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\ParseOutcome;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CountryDispatchTest extends TestCase
{
    /** MedCom reserved serial, resolving to 1948-12-25. */
    private const string DANISH = '251248-9996';

    private const string SWEDISH = '190312049802';

    /**
     * Individual 151, dnr-000-499 series, resolving to 1965-03-01. Same
     * value NorwegianDNumberSchemeTest calls SYNTHETIC_D_NUMBER -- see that
     * file for provenance.
     */
    private const string NORWEGIAN_D_NUMBER = '41836515143';

    /**
     * Day 41 (-> 1), month 83 (-> March), year 30, individual 500
     * (dnr-500-999, no year cap) -> a D-number birth date of 2030-03-01,
     * which is future relative to the reference date below. Constructed
     * rather than sourced -- checksum verified by hand against
     * NorwegianChecksum's own weight vectors -- and safe under CLAUDE.md's
     * Exception 1 for the same reason as every other synthetic value in
     * this project: the written month is 81-92, so no bearer can exist.
     *
     * Chosen specifically because its day (41) ALSO satisfied the
     * fødselsnummer's SHAPE before that shape was restricted to 01-31 --
     * with the two schemes' shapes overlapping, this same input made the
     * fødselsnummer scheme fail with ImpossibleDate (day 41 is never a real
     * calendar day) while the D-number scheme correctly reported
     * FutureBirthDate, and mostSpecificFailure's insertion-order tie-break
     * between two rank-3 failures surfaced the wrong one. See
     * NorwegianNationalIdentityNumberScheme's SHAPE comment.
     */
    private const string AMBIGUOUS_DAY_FUTURE_D_NUMBER = '41833050077';

    #[Test]
    public function parsesADanishNumberWhenDenmarkIsNamed(): void
    {
        $this->assertTrue($this->explain(self::DANISH, Country::Denmark)->succeeded());
    }

    #[Test]
    public function resolvesADanishNumberToTheCprScheme(): void
    {
        $this->assertSame(
            Scheme::DkCprNumber,
            $this->explain(self::DANISH, Country::Denmark)->number()?->scheme(),
        );
    }

    #[Test]
    public function keepsTheDanishCanonicalFormAtTenCharacters(): void
    {
        $this->assertSame(
            '2512489996',
            $this->explain(self::DANISH, Country::Denmark)->number()?->canonical(),
        );
    }

    #[Test]
    public function stillParsesASwedishNumberWhenSwedenIsNamed(): void
    {
        $this->assertSame(
            self::SWEDISH,
            $this->explain(self::SWEDISH, Country::Sweden)->number()?->canonical(),
        );
    }

    /**
     * There is no country with no scheme any more: every Country member is in
     * SUPPORTED_COUNTRIES, so countriesToTry() can no longer return an empty
     * list and CountryNotSupported is unreachable through explain(). The guard
     * that produces it stays, because README.md §12 step 1 gives the next
     * country a Country member and a recognize-only shape before it gets a
     * scheme — that is the state the guard exists for, and it is a state this
     * package is simply not in today.
     *
     * What the same input pins instead is the behaviour that replaced it, and
     * it is more useful than the refusal was: asking the wrong country now gets
     * that country's own refusal — Finland runs modulus-31 over these eleven
     * characters and rejects them — plus a hint at the country that would have
     * accepted it.
     */
    #[Test]
    public function askingTheWrongCountryGetsThatCountrysOwnRefusalAndAHintAtTheRightOne(): void
    {
        $outcome = $this->explain(self::DANISH, Country::Finland);

        $this->assertSame(ParseFailure::ChecksumMismatch, $outcome->failure());
        $this->assertSame(Country::Denmark, $outcome->recognizedCountry());
    }

    /**
     * With one country named only that country's scheme runs, so its reason is
     * the answer. Reporting a generic rejection here would lose the single most
     * useful thing Swedish validation knows, and there are fixtures pinning it.
     */
    #[Test]
    public function reportsTheSchemesOwnFailureWhenOneCountryIsNamed(): void
    {
        $this->assertSame(
            ParseFailure::ChecksumMismatch,
            $this->explain('190312049803', Country::Sweden)->failure(),
        );
    }

    /**
     * With no country named, several schemes ran and all refused. No single
     * scheme's complaint answers "what is this?", so the summary is the honest
     * reply rather than whichever scheme happened to be consulted first.
     */
    #[Test]
    public function reportsAGenericFailureWhenNoCountryIsNamed(): void
    {
        $this->assertSame(
            ParseFailure::NotAnIdentityNumber,
            $this->explain('190312049803', null)->failure(),
        );
    }

    #[Test]
    public function resolvesANorwegianDNumberThroughExplainWithNoCountryNamed(): void
    {
        $outcome = $this->explain(self::NORWEGIAN_D_NUMBER, null, true);

        $this->assertTrue($outcome->succeeded());
        $this->assertCount(1, $outcome->candidates());
        $this->assertSame(Scheme::NoDNumber, $outcome->number()?->scheme());
    }

    #[Test]
    public function recognizesANorwegianDNumberHandedToSwedenAsNorwegian(): void
    {
        $outcome = $this->explain(self::NORWEGIAN_D_NUMBER, Country::Sweden, true);

        $this->assertSame(ParseFailure::UnsupportedScheme, $outcome->failure());
        $this->assertSame(Country::Norway, $outcome->recognizedCountry());
    }

    /**
     * Regression: before the fødselsnummer's SHAPE was restricted to day
     * 01-31, this D-number-shaped value matched both Norwegian schemes, and
     * the two rank-3 failures (the fødselsnummer's ImpossibleDate, the
     * D-number's own FutureBirthDate) tied under mostSpecificFailure's
     * insertion-order tie-break -- the fødselsnummer ran first in
     * SupportedSchemes::resultsFor(), so its wrong-for-this-value answer won
     * regardless of which was actually correct. A scheme-level test cannot
     * catch this: each scheme in isolation was already correct. Only the
     * dispatcher, which runs both, can.
     */
    #[Test]
    public function aDNumbersOwnFutureBirthDateIsNotShadowedByTheFodselsnummersNowDisjointDayRange(): void
    {
        $this->assertSame(
            ParseFailure::FutureBirthDate,
            $this->explain(self::AMBIGUOUS_DAY_FUTURE_D_NUMBER, Country::Norway, true)->failure(),
        );
    }

    private function explain(string $input, ?Country $issuedBy, bool $allowSyntheticNumbers = false): ParseOutcome
    {
        return PersonalIdentityNumber::explain(
            $input,
            $issuedBy,
            new ParseOptions(
                new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')),
                allowSyntheticNumbers: $allowSyntheticNumbers,
            ),
        );
    }
}
