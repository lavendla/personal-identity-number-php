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

    /**
     * DVV's own published example with its control character deliberately
     * swapped, so it fails Finland's modulus-31 check and has no bearer — see
     * spec/fixtures/foreign/PROVENANCE.md.
     */
    private const string UNPARSEABLE_EVERYWHERE = '131052-308U';

    /**
     * DVV's published test identity 010280-952L, valid under Finland's own
     * scheme: individual number 952, inside the 900-999 block DVV states it
     * does not issue from, so it identifies nobody — CLAUDE.md's Exception 3.
     *
     * It replaces the checksum-invalid code above in the recognition test
     * below, because "recognized, but no candidate" is no longer a state any
     * country can be in: every country is on the real-parse tier now, so a
     * country that recognizes a value has also produced a number for it. What
     * remains, and is what the test now asserts, is the recognition a *named*
     * country's refusal carries — Sweden asked, Sweden refuses, Finland would
     * have accepted.
     */
    private const string FINNISH = '010280-952L';

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
     * Decided here rather than left to fall out of the implementation, because
     * it is public behaviour: detect() reports every interpretation that
     * produced a number, and never a recognition. A value no registry accepts
     * therefore gives an empty list rather than a list naming the country whose
     * shape it matched — anything else would mean inventing a
     * PersonalIdentityNumber for a value no registry would issue.
     */
    #[Test]
    public function detectReportsNoCandidateForAValueNoRegistryAccepts(): void
    {
        $this->assertSame(
            [],
            PersonalIdentityNumber::detect(self::UNPARSEABLE_EVERYWHERE, $this->options()),
        );
    }

    /**
     * The recognition explain() can give and detect() cannot. Asked as Swedish,
     * so a country *is* named: Sweden refuses, and the hint says the value would
     * have been accepted in Finland. With no country named, Finland is consulted
     * for real and this same value parses, so there is no failure left to attach
     * a hint to — which is why this test names a country and the one above does
     * not.
     */
    #[Test]
    public function explainReportsARecognitionDetectCannot(): void
    {
        $this->assertSame(
            Country::Finland,
            PersonalIdentityNumber::explain(self::FINNISH, Country::Sweden, $this->options())
                ->recognizedCountry(),
        );
    }

    private function options(): ParseOptions
    {
        return new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')));
    }
}
