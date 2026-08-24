<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImplausibleBirthDateTest extends TestCase
{
    /**
     * Century "00", so a resolved year of 0012. Safe to name because
     * Skatteverket has never issued that century, which is the carve-out this
     * value has relied on since Plan 1.
     */
    private const string YEAR_TWELVE = '001203049802';

    #[Test]
    public function aYearBeforeTheFloorIsImplausibleRatherThanImpossible(): void
    {
        $this->assertSame(ParseFailure::ImplausibleBirthDate, $this->failureFor(self::YEAR_TWELVE));
    }

    /**
     * The distinction is the point of the member existing. "That date does not
     * exist" and "that date exists but nobody alive was born then" are
     * different information, and a consumer bucketing contaminated data has to
     * be able to tell them apart.
     */
    #[Test]
    public function aDateThatDoesNotExistIsStillImpossible(): void
    {
        $this->assertSame(ParseFailure::ImpossibleDate, $this->failureFor('20260230-2380'));
    }

    #[Test]
    public function aPlausibleHistoricalYearStillParses(): void
    {
        $outcome = PersonalIdentityNumber::explain(
            '19031204-9802',
            Country::Sweden,
            new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'))),
        );

        $this->assertTrue($outcome->succeeded());
    }

    private function failureFor(string $input): ?ParseFailure
    {
        return PersonalIdentityNumber::explain(
            $input,
            Country::Sweden,
            new ParseOptions(new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'))),
        )->failure();
    }
}
