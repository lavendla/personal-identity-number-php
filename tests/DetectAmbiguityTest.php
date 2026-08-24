<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\ParseOutcome;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Sweden and Denmark collide, and this is the shape of it.
 *
 * 2601012384 is the ten-digit short form of Skatteverket's published test
 * number 202601012384. Read as Swedish it is 260101-2384, born 2026-01-01 and
 * Luhn-valid. Read as Danish it is 260101-2384, born 1901-01-26, because DDMMYY
 * puts the day first and serial 2384 resolves to the 1900s. Both readings are
 * completely valid. They are 125 years apart.
 *
 * Denmark has no checksum to break the tie, so no amount of cleverness inside
 * this package can choose between them — which is why parse() requires a
 * country and detect() reports every interpretation instead of a best match.
 * A "best match" would put a 2026 infant and an 1875-born Dane in the same
 * bucket and pick one.
 */
final class DetectAmbiguityTest extends TestCase
{
    private const string AMBIGUOUS = '2601012384';

    /** Fails Norway's modulus-11 check, so it has no bearer — see spec/fixtures/foreign/PROVENANCE.md. */
    private const string NORWEGIAN = '13108633528';

    #[Test]
    public function detectReturnsOneCandidatePerRegistryThatAccepts(): void
    {
        $this->assertCount(2, $this->detect());
    }

    #[Test]
    public function detectNamesBothRegistries(): void
    {
        $countries = array_map(
            static fn(PersonalIdentityNumber $candidate): string => $candidate->country()->value,
            $this->detect(),
        );

        sort($countries);

        $this->assertSame([Country::Denmark->value, Country::Sweden->value], $countries);
    }

    #[Test]
    public function theTwoReadingsDisagreeAboutTheBirthDate(): void
    {
        $birthDates = array_map(
            static fn(PersonalIdentityNumber $candidate): ?string => $candidate->birthDate()?->format('Y-m-d'),
            $this->detect(),
        );

        sort($birthDates);

        $this->assertSame(['1901-01-26', '2026-01-01'], $birthDates);
    }

    /**
     * The regression guard for the decision recorded in docs/decision-log.md:
     * detect() returns every interpretation and never a best match. number()
     * returning null on ambiguity is what stops a caller silently committing a
     * person to the wrong country.
     */
    #[Test]
    public function anAmbiguousOutcomeYieldsNoSingleNumber(): void
    {
        $this->assertNull($this->outcome()->number());
    }

    #[Test]
    public function anAmbiguousOutcomeSaysSoOutright(): void
    {
        $this->assertTrue($this->outcome()->isAmbiguous());
    }

    /** @return list<PersonalIdentityNumber> */
    private function detect(): array
    {
        return PersonalIdentityNumber::detect(self::AMBIGUOUS, $this->options());
    }

    private function outcome(): ParseOutcome
    {
        return PersonalIdentityNumber::explain(self::AMBIGUOUS, null, $this->options());
    }

    /**
     * Decided here rather than left to fall out of the implementation, because it
     * is new public behaviour: detect() reports every interpretation that
     * produced a number, and a recognize-only scheme produces none. So a
     * Norwegian value gives an empty list, and the recognition is available only
     * through explain(). Anything else would mean inventing a
     * PersonalIdentityNumber for a country whose numbers this package cannot
     * canonicalise, validate or derive anything from.
     */
    #[Test]
    public function detectReportsNoCandidateForARecognizedForeignNumber(): void
    {
        $this->assertSame([], PersonalIdentityNumber::detect(self::NORWEGIAN, $this->options()));
    }

    #[Test]
    public function explainStillReportsTheRecognitionDetectCannot(): void
    {
        $this->assertSame(
            Country::Norway,
            PersonalIdentityNumber::explain(self::NORWEGIAN, null, $this->options())->recognizedCountry(),
        );
    }

    private function options(): ParseOptions
    {
        return new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')));
    }
}
