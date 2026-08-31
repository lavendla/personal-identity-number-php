<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\FinnishChecksum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FinnishChecksumTest extends TestCase
{
    #[Test]
    public function itReproducesDvvsOwnPublishedExample(): void
    {
        $this->assertTrue(FinnishChecksum::matches('131052-308T'));
    }

    /**
     * These are also the digits of a fixture in
     * spec/fixtures/foreign/no-fi.json, so this test proves the helper agrees
     * with the corpus rather than only with itself: 131052308 mod 31 = 25,
     * which is 'T', so a trailing U cannot be right.
     */
    #[Test]
    public function itRejectsTheSameCodeWithADifferentControlCharacter(): void
    {
        $this->assertFalse(FinnishChecksum::matches('131052-308U'));
    }

    /**
     * 010280952 mod 31 = 19, and index 19 of the alphabet is 'L' -- a letter. A
     * helper that returned the remainder's own digits would fail here and pass
     * on a digit control character. A published DVV identity.
     */
    #[Test]
    public function itIndexesTheAlphabetRatherThanTheDigits(): void
    {
        $this->assertTrue(FinnishChecksum::matches('010280-952L'));
    }

    /**
     * 020594903 mod 31 = 22 -> 'P'. The marker sits between the two digit runs
     * and is deliberately not part of the checksummed value: the same nine
     * digits with a different marker keep the same control character.
     */
    #[Test]
    public function itIgnoresTheIntermediateCharacterWhenComputingTheRemainder(): void
    {
        $this->assertTrue(FinnishChecksum::matches('020594X903P'));
        $this->assertTrue(FinnishChecksum::matches('020594A903P'));
    }
}
