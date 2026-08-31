<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    #[Test]
    public function schemeCountryAnswersFromTheSchemeFileRatherThanAFallbackBranch(): void
    {
        $this->assertSame(Country::Denmark, Scheme::DkCprNumber->country());
    }

    #[Test]
    public function schemeCountryHasAnEntryForEveryDeclaredScheme(): void
    {
        foreach (Scheme::cases() as $scheme) {
            $this->assertInstanceOf(Country::class, $scheme->country());
        }
    }
}
