<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Conformance;

use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\Format;
use Lavendla\PersonalIdentityNumber\ParseOutcome;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConformanceTest extends TestCase
{
    /** @return array<string, array{0: array<string, mixed>}> */
    public static function cases(): array
    {
        return FixtureLoader::all();
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function outcomeMatches(array $case): void
    {
        $this->assertSame($case['outcome'], $this->outcomeStateOf($case));
    }

    /**
     * Three states, not two. succeeded() is true for an ambiguous outcome —
     * failure is null and there are candidates — so comparing it against
     * `outcome === 'parsed'` reported an ambiguity fixture as a failure of the
     * wrong kind. Naming all three makes the fixture's outcome field mean
     * exactly one thing.
     *
     * @param array<string, mixed> $case
     */
    private function outcomeStateOf(array $case): string
    {
        $outcome = $this->explain($case);

        if ($outcome->isAmbiguous()) {
            return 'ambiguous';
        }

        return $outcome->succeeded() ? 'parsed' : 'failed';
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function canonicalMatches(array $case): void
    {
        if (! array_key_exists('canonical', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertSame($case['canonical'], $this->explain($case)->number()?->canonical());
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function schemeMatches(array $case): void
    {
        if (! array_key_exists('scheme', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertSame($case['scheme'], $this->explain($case)->number()?->scheme()->value);
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function failureMatches(array $case): void
    {
        if (! array_key_exists('failure', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertSame($case['failure'], $this->explain($case)->failure()?->value);
    }

    /**
     * Read with array_key_exists rather than isset, for the reason §3.17
     * records: isset() is false for an explicit null, so a fixture asserting
     * "no country was recognized" would be silently skipped here while the
     * TypeScript twin ran it.
     *
     * @param array<string, mixed> $case
     */
    #[Test]
    #[DataProvider('cases')]
    public function recognizedCountryMatches(array $case): void
    {
        if (! array_key_exists('recognizedCountry', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertSame($case['recognizedCountry'], $this->explain($case)->recognizedCountry()?->value);
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function birthDateMatches(array $case): void
    {
        if (! array_key_exists('birthDate', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertSame($case['birthDate'], $this->explain($case)->number()?->birthDate()?->format('Y-m-d'));
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function genderMatches(array $case): void
    {
        if (! array_key_exists('gender', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertSame($case['gender'], $this->explain($case)->number()?->gender()?->value);
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function formatsMatch(array $case): void
    {
        if (! array_key_exists('formats', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $number = $this->explain($case)->number();

        /** @var array<string, string> $formats */
        $formats = $case['formats'];

        foreach (Format::cases() as $format) {
            if (! isset($formats[$format->value])) {
                continue;
            }

            $this->assertSame($formats[$format->value], $number?->format($format));
        }
    }

    /** @param array<string, mixed> $case */
    #[Test]
    #[DataProvider('cases')]
    public function isPersonMatches(array $case): void
    {
        if (! array_key_exists('isPerson', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        $this->assertSame($case['isPerson'], $this->explain($case)->number()?->isPerson());
    }

    /**
     * Sorted both sides, because "which registries accept this" is a set and the
     * order candidates come back in is scheme-registration order -- an
     * implementation detail no fixture should be pinning by accident.
     *
     * Always calls detect(), which names no country, regardless of whether the
     * fixture carries an issuedBy: detect()'s whole question is what the value
     * is when nobody says.
     *
     * @param array<string, mixed> $case
     */
    #[Test]
    #[DataProvider('cases')]
    public function detectedSchemesMatch(array $case): void
    {
        if (! array_key_exists('detected', $case)) {
            $this->expectNotToPerformAssertions();

            return;
        }

        /** @var list<string> $expected */
        $expected = $case['detected'];
        sort($expected);

        $schemes = array_map(
            static fn(PersonalIdentityNumber $candidate): string => $candidate->scheme()->value,
            PersonalIdentityNumber::detect(
                FixtureLoader::stringField($case, 'input'),
                FixtureLoader::parseOptions($case),
            ),
        );
        sort($schemes);

        $this->assertSame($expected, $schemes);
    }

    /** @param array<string, mixed> $case */
    private function explain(array $case): ParseOutcome
    {
        // An absent issuedBy means no country is named, so every scheme runs.
        // That is how an ambiguity fixture asks its question — the collision is
        // a property of asking without a country, not of the value.
        $issuedBy = FixtureLoader::optionalStringField($case, 'issuedBy');

        return PersonalIdentityNumber::explain(
            FixtureLoader::stringField($case, 'input'),
            $issuedBy === null ? null : Country::from($issuedBy),
            FixtureLoader::parseOptions($case),
        );
    }
}
