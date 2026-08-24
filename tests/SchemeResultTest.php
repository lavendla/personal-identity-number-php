<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\SchemeResult;
use Lavendla\PersonalIdentityNumber\Schemes\SwedishPersonalNumberScheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SchemeResultTest extends TestCase
{
    /**
     * A coordination number whose stored day is 61, so the +60 offset is
     * visible in the assertion: the canonical form must keep 61 because that
     * is what the number is, while the birth date must read 1 because that is
     * when the person was born. A fixture drawn from the pool at random would
     * usually have a stored day in the twenties or thirties, where subtracting
     * 60 or not subtracting it both look plausible and the test proves nothing.
     */
    private const string COORDINATION_NUMBER = '19160161-2383';

    #[Test]
    public function theSchemeBuildsItsOwnCanonicalForm(): void
    {
        $this->assertSame('190312049802', $this->accepted('19031204-9802')->canonical);
    }

    #[Test]
    public function theSchemeResolvesItsOwnGender(): void
    {
        $this->assertSame(Gender::Female, $this->accepted('19031204-9802')->gender);
    }

    #[Test]
    public function theSchemeResolvesItsOwnBirthDate(): void
    {
        $this->assertSame('1903-12-04', $this->accepted('19031204-9802')->birthTime?->toBirthDate()?->iso());
    }

    #[Test]
    public function aCoordinationNumberKeepsTheOffsetDayInTheCanonicalForm(): void
    {
        $this->assertSame('191601612383', $this->accepted(self::COORDINATION_NUMBER)->canonical);
    }

    #[Test]
    public function aCoordinationNumberReportsTheRealBirthDay(): void
    {
        $this->assertSame(1, $this->accepted(self::COORDINATION_NUMBER)->birthTime?->day);
    }

    #[Test]
    public function aCoordinationNumberCarriesItsOwnScheme(): void
    {
        $this->assertSame(Scheme::SeCoordinationNumber, $this->accepted(self::COORDINATION_NUMBER)->scheme);
    }

    private function accepted(string $input): SchemeResult
    {
        $result = SwedishPersonalNumberScheme::parse(
            $input,
            new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC')),
        );

        $this->assertInstanceOf(SchemeResult::class, $result);

        return $result;
    }
}
