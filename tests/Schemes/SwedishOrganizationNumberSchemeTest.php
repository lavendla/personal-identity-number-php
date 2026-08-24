<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Schemes;

use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\SchemeResult;
use Lavendla\PersonalIdentityNumber\Schemes\SwedishOrganizationNumberScheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SwedishOrganizationNumberSchemeTest extends TestCase
{
    /** Skatteverket's own organization number, published by Skatteverket. Third digit 2. */
    private const string AGENCY = '2021005448';

    /** Already twelve digits with the 16 legal-person prefix. Third digit of the body is 6. */
    private const string PREFIXED = '165560360793';

    /**
     * A published Skatteverket personal number. Its third digit is 0, because that
     * digit is the first digit of the month, which is exactly why 4 § lagen
     * (1974:174) fixes an organization number's at 2 or above.
     */
    private const string PERSONAL_NUMBER = '0001019801';

    #[Test]
    public function acceptsATenDigitNumber(): void
    {
        $this->assertInstanceOf(SchemeResult::class, $this->parse(self::AGENCY));
    }

    #[Test]
    public function acceptsASeparator(): void
    {
        $this->assertSame('162021005448', $this->accepted('202100-5448')->canonical);
    }

    #[Test]
    public function addsTheLegalPersonPrefixToTheCanonicalForm(): void
    {
        $this->assertSame('162021005448', $this->accepted(self::AGENCY)->canonical);
    }

    #[Test]
    public function leavesAnAlreadyPrefixedNumberUnchanged(): void
    {
        $this->assertSame(self::PREFIXED, $this->accepted(self::PREFIXED)->canonical);
    }

    #[Test]
    public function resolvesTheSchemeAsOrganizationNumber(): void
    {
        $this->assertSame(Scheme::SeOrganizationNumber, $this->accepted(self::AGENCY)->scheme);
    }

    #[Test]
    public function hasNoBirthTime(): void
    {
        $this->assertNull($this->accepted(self::AGENCY)->birthTime);
    }

    #[Test]
    public function hasNoGender(): void
    {
        $this->assertNull($this->accepted(self::AGENCY)->gender);
    }

    /**
     * The case that proves the third-digit rule does work rather than decorating
     * the scheme file. There is no separate "third digit below 2" case to write:
     * a personnummer *is* such a number.
     */
    #[Test]
    public function rejectsAPersonalNumberOnTheThirdDigitRule(): void
    {
        $this->assertSame(ParseFailure::NotAnIdentityNumber, $this->parse(self::PERSONAL_NUMBER));
    }

    #[Test]
    public function rejectsABadCheckDigit(): void
    {
        $this->assertSame(ParseFailure::ChecksumMismatch, $this->parse('2021005449'));
    }

    /**
     * Recognition first, exclusion second. A scheme that never ran could not say
     * this, and the caller would get NotAnIdentityNumber for their own option.
     */
    #[Test]
    public function reportsSchemeNotAllowedWhenOrganizationNumbersAreExcluded(): void
    {
        $this->assertSame(
            ParseFailure::SchemeNotAllowed,
            SwedishOrganizationNumberScheme::parse(self::AGENCY, false),
        );
    }

    #[Test]
    public function rejectsAShapeThatIsNeitherTenNorTwelveDigits(): void
    {
        $this->assertSame(ParseFailure::NotAnIdentityNumber, $this->parse('20210054'));
    }

    private function parse(string $input): SchemeResult|ParseFailure
    {
        return SwedishOrganizationNumberScheme::parse($input);
    }

    /** Narrows the union so PHPStan permits property access at level max. */
    private function accepted(string $input): SchemeResult
    {
        $result = $this->parse($input);

        $this->assertInstanceOf(SchemeResult::class, $result);

        return $result;
    }
}
