<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Checksum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ChecksumTest extends TestCase
{
    #[Test]
    public function itComputesTheExpectedCheckDigit(): void
    {
        $this->assertSame(0, Checksum::luhn('870101238'));
    }

    #[Test]
    public function itComputesZeroWhenTheSumIsAlreadyAMultipleOfTen(): void
    {
        $this->assertSame(0, Checksum::luhn('000000000'));
    }
}
