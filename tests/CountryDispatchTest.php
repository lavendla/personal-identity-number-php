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

    #[Test]
    public function refusesACountryWithNoScheme(): void
    {
        $this->assertSame(
            ParseFailure::CountryNotSupported,
            $this->explain(self::DANISH, Country::Norway)->failure(),
        );
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

    private function explain(string $input, ?Country $issuedBy): ParseOutcome
    {
        return PersonalIdentityNumber::explain(
            $input,
            $issuedBy,
            new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'))),
        );
    }
}
