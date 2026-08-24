<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\Exceptions\ParseException;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\ParseOutcome;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrganizationNumberDispatchTest extends TestCase
{
    /** Skatteverket's own organization number, published by Skatteverket. Third digit 2. */
    private const string ORGANIZATION = '202100-5448';

    private const string PERSONAL = '190312049802';

    /** The same personal number with its check digit incremented. */
    private const string BAD_CHECKSUM = '190312049803';

    #[Test]
    public function anOrganizationNumberParsesThroughThePublicApi(): void
    {
        $this->assertTrue($this->explain(self::ORGANIZATION)->succeeded());
    }

    #[Test]
    public function anOrganizationNumberCanonicalisesWithTheLegalPersonPrefix(): void
    {
        $this->assertSame('162021005448', $this->explain(self::ORGANIZATION)->number()?->canonical());
    }

    #[Test]
    public function anOrganizationNumberIsNotAPerson(): void
    {
        $this->assertFalse($this->explain(self::ORGANIZATION)->number()?->isPerson());
    }

    #[Test]
    public function anOrganizationNumberHasNoBirthDate(): void
    {
        $this->assertNull($this->explain(self::ORGANIZATION)->number()?->birthDate());
    }

    #[Test]
    public function anOrganizationNumberHasNoGender(): void
    {
        $this->assertNull($this->explain(self::ORGANIZATION)->number()?->gender());
    }

    /**
     * An organization number encodes no birth date, so no century has to be
     * inferred and no reference date is needed — the same property Denmark has
     * for a different reason.
     */
    #[Test]
    public function anOrganizationNumberNeedsNoReferenceDate(): void
    {
        $outcome = PersonalIdentityNumber::explain(
            self::ORGANIZATION,
            Country::Sweden,
            ParseOptions::forCenturyCompleteInput(),
        );

        $this->assertTrue($outcome->succeeded());
    }

    #[Test]
    public function excludingOrganizationNumbersReportsSchemeNotAllowed(): void
    {
        $outcome = PersonalIdentityNumber::explain(
            self::ORGANIZATION,
            Country::Sweden,
            new ParseOptions($this->referenceDate(), allowOrganizationNumber: false),
        );

        $this->assertSame(ParseFailure::SchemeNotAllowed, $outcome->failure());
    }

    /**
     * The precedence guard. Sweden now runs two schemes, and the organization
     * scheme answers NotAnIdentityNumber for a personal number's shape. If the
     * generic answer won, this would report NotAnIdentityNumber and fourteen
     * fixture assertions would go with it.
     */
    #[Test]
    public function aBadCheckDigitStillReportsChecksumMismatch(): void
    {
        $this->assertSame(ParseFailure::ChecksumMismatch, $this->explain(self::BAD_CHECKSUM)->failure());
    }

    #[Test]
    public function parseForOrganizationReturnsAnOrganizationNumber(): void
    {
        $number = PersonalIdentityNumber::parseForOrganization(
            self::ORGANIZATION,
            Country::Sweden,
            $this->referenceDate(),
        );

        $this->assertSame(Scheme::SeOrganizationNumber, $number->scheme());
    }

    #[Test]
    public function parseForPersonRejectsAnOrganizationNumber(): void
    {
        try {
            PersonalIdentityNumber::parseForPerson(self::ORGANIZATION, Country::Sweden, $this->referenceDate());
            $this->fail('Expected a ParseException.');
        } catch (ParseException $exception) {
            $this->assertSame(ParseFailure::SchemeNotAllowed, $exception->getFailure());
        }
    }

    #[Test]
    public function parseForPersonStillAcceptsAPersonalNumber(): void
    {
        $number = PersonalIdentityNumber::parseForPerson(self::PERSONAL, Country::Sweden, $this->referenceDate());

        $this->assertSame(Scheme::SePersonalNumber, $number->scheme());
    }

    private function explain(string $input): ParseOutcome
    {
        return PersonalIdentityNumber::explain(
            $input,
            Country::Sweden,
            new ParseOptions($this->referenceDate()),
        );
    }

    private function referenceDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-16', new DateTimeZone('UTC'));
    }
}
