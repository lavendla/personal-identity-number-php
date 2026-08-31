<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\NorwegianChecksum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NorwegianChecksumTest extends TestCase
{
    #[Test]
    public function itAcceptsANumberWhoseCheckDigitsAreCorrect(): void
    {
        $this->assertTrue(NorwegianChecksum::matches('13108633527'));
    }

    #[Test]
    public function itRejectsTheSameDigitsWithTheWrongCheckDigits(): void
    {
        $this->assertFalse(NorwegianChecksum::matches('13108633528'));
    }

    #[Test]
    public function itRejectsACombinationWhoseFirstCheckDigitComputesToTen(): void
    {
        // 018185006 -> k1 = 10. A check digit is one digit, so the registry
        // skips the combination entirely; no final two digits can make it valid.
        $this->assertFalse(NorwegianChecksum::matches('01818500600'));
    }

    #[Test]
    public function itRejectsAValidCombinationCarryingTheWrongCheckDigits(): void
    {
        // 010101000 -> 50. This is an ordinary mismatch, not a skipped combination.
        $this->assertFalse(NorwegianChecksum::matches('01010100024'));
    }

    #[Test]
    public function itRejectsACombinationWhoseSecondCheckDigitComputesToTen(): void
    {
        // 000000014 -> k1 = 9, k2 = 10. The first check digit matches, so the
        // second branch is reached and must reject on its own computed-to-ten case.
        $this->assertFalse(NorwegianChecksum::matches('00000001490'));
    }
}
