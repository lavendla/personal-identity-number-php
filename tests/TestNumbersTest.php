<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use Lavendla\PersonalIdentityNumber\TestNumbers;
use OutOfRangeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TestNumbersTest extends TestCase
{
    #[Test]
    #[DataProvider('pooledSchemes')]
    public function itPoolsNumbersForTheScheme(Scheme $scheme): void
    {
        $this->assertNotEmpty(TestNumbers::pool($scheme));
    }

    /**
     * The pool's whole promise is that a drawn value is a usable identity
     * number, so the library validates its own data rather than trusting the
     * source files to have stayed well-formed through codegen.
     */
    #[Test]
    #[DataProvider('pooledSchemes')]
    public function itPoolsOnlyValidNumbers(Scheme $scheme): void
    {
        // After the newest cohort's last birth date. Skatteverket's 2026 series
        // runs to 2026-12-31, and a birth date in the future is implausible
        // rather than invalid — so an earlier reference date would fail two
        // hundred perfectly good numbers and say nothing about the pool.
        $options = new ParseOptions(new DateTimeImmutable('2027-01-01'));

        $invalid = array_filter(
            TestNumbers::pool($scheme),
            static fn(string $number): bool => ! PersonalIdentityNumber::validates(
                $number,
                $scheme->country(),
                $options,
            ),
        );

        $this->assertSame([], $invalid);
    }

    #[Test]
    public function itDrawsTheSameNumberForTheSameKey(): void
    {
        $this->assertSame(
            TestNumbers::draw(Scheme::SePersonalNumber, 'customer:no-email-charles'),
            TestNumbers::draw(Scheme::SePersonalNumber, 'customer:no-email-charles'),
        );
    }

    #[Test]
    public function itDrawsDifferentNumbersForDifferentKeys(): void
    {
        $this->assertNotSame(
            TestNumbers::draw(Scheme::SePersonalNumber, 'customer:no-email-charles'),
            TestNumbers::draw(Scheme::SePersonalNumber, 'deceased:margareta-svensson'),
        );
    }

    #[Test]
    public function itDrawsFromThePool(): void
    {
        $this->assertContains(
            TestNumbers::draw(Scheme::DkCprNumber, 'customer:erik-svensson'),
            TestNumbers::pool(Scheme::DkCprNumber),
        );
    }

    /**
     * Loud rather than empty: a scheme gains a pool by someone adding an
     * official published source for it, and silently returning nothing would
     * let a consumer seed a country it has no safe numbers for.
     */
    #[Test]
    public function itRefusesASchemeWithNoPool(): void
    {
        $this->expectException(OutOfRangeException::class);

        TestNumbers::pool(Scheme::NoNationalIdentityNumber);
    }

    /** @return array<string, array{Scheme}> */
    public static function pooledSchemes(): array
    {
        return [
            'swedish personal number'     => [Scheme::SePersonalNumber],
            'swedish coordination number' => [Scheme::SeCoordinationNumber],
            'danish cpr number'           => [Scheme::DkCprNumber],
        ];
    }
}
