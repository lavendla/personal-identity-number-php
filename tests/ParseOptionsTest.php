<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\ParseOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ParseOptionsTest extends TestCase
{
    #[Test]
    public function defaultsToRefusingSyntheticNumbersUnlikeEveryOtherFlag(): void
    {
        $options = new ParseOptions(null);

        $this->assertFalse($options->allowSyntheticNumbers);
    }

    #[Test]
    public function allowsEveryOtherFlagToDefaultPermissive(): void
    {
        $options = new ParseOptions(null);

        $this->assertTrue($options->allowCoordinationNumber);
        $this->assertTrue($options->allowOrganizationNumber);
        $this->assertTrue($options->allowUnknownBirthNumber);
    }

    #[Test]
    public function letsTheCallerOptIntoSyntheticNumbersExplicitly(): void
    {
        $options = new ParseOptions(null, allowSyntheticNumbers: true);

        $this->assertTrue($options->allowSyntheticNumbers);
    }
}
