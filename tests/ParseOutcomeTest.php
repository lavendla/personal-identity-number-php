<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\ParseOutcome;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * candidateCountries() reports the countries the candidates resolved under and
 * nothing else. It is deliberately not a country for the outcome: a failed
 * outcome has none, and recognizedCountry() stays its own accessor because a
 * recognition is a hint about a value nobody parsed, not a result.
 */
final class ParseOutcomeTest extends TestCase
{
    /** Skatteverket's published test number, Luhn-valid and born 2026-01-01. */
    private const string SWEDISH_PERSON = '202601012384';

    /**
     * Skatteverket's own organization number, third digit 2 -- CLAUDE.md's
     * Exception 2. Paired with the personal number above it is the only way to
     * reach the deduplication branch: no single input yields two candidates from
     * one country today, because Sweden's personal and coordination schemes split
     * on the day range and Norway's two split on the +40 day offset.
     */
    private const string SWEDISH_ORGANIZATION = '202100-5448';

    /** Ten digits valid under both Sweden's and Denmark's schemes, 125 years apart. */
    private const string AMBIGUOUS = '2601012384';

    /**
     * Carries a recognizedCountry precisely so this cannot pass by accident: an
     * implementation falling back to the recognition would report Denmark here.
     */
    #[Test]
    public function aFailedOutcomeHasNoCandidateCountries(): void
    {
        $outcome = ParseOutcome::failed(ParseFailure::NotAnIdentityNumber, Country::Denmark);

        $this->assertSame([], $outcome->candidateCountries());
    }

    #[Test]
    public function aSingleCandidateReportsItsOwnCountry(): void
    {
        $outcome = PersonalIdentityNumber::explain(self::SWEDISH_PERSON, Country::Sweden, $this->options());

        $this->assertSame([Country::Sweden], $outcome->candidateCountries());
    }

    /** In candidate order, which is the registry order, and is not a ranking. */
    #[Test]
    public function anAmbiguousOutcomeReportsEveryCountryInPlay(): void
    {
        $outcome = PersonalIdentityNumber::explain(self::AMBIGUOUS, null, $this->options());

        $this->assertSame([Country::Sweden, Country::Denmark], $outcome->candidateCountries());
    }

    #[Test]
    public function twoSchemesFromOneCountryCollapseToOneEntry(): void
    {
        $outcome = ParseOutcome::resolved([
            $this->parse(self::SWEDISH_PERSON),
            $this->parse(self::SWEDISH_ORGANIZATION),
        ]);

        $this->assertSame([Country::Sweden], $outcome->candidateCountries());
    }

    private function parse(string $raw): PersonalIdentityNumber
    {
        return PersonalIdentityNumber::parse($raw, Country::Sweden, $this->options());
    }

    private function options(): ParseOptions
    {
        return new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')));
    }
}
