<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NormalizerTest extends TestCase
{
    #[Test]
    #[DataProvider('foldedInputs')]
    public function itFoldsToTheExpectedValue(string $raw, string $expected): void
    {
        $this->assertSame($expected, Normalizer::normalize($raw));
    }

    /** @return iterable<string, array{string, string}> */
    public static function foldedInputs(): iterable
    {
        yield 'ordinary space' => ['870101 2384', '8701012384'];
        yield 'non-breaking space' => ["870101\u{00a0}2384", '8701012384'];
        yield 'narrow no-break space' => ["870101\u{202f}2384", '8701012384'];
        yield 'en dash' => ["870101\u{2013}2384", '870101-2384'];
        yield 'em dash' => ["870101\u{2014}2384", '870101-2384'];
        yield 'minus sign' => ["870101\u{2212}2384", '870101-2384'];
        yield 'plus separator survives' => ['870101+2384', '870101+2384'];
    }

    #[Test]
    #[DataProvider('rejectedInputs')]
    public function itRejectsCharactersOutsideTheAllowList(string $raw): void
    {
        $this->assertNull(Normalizer::normalize($raw));
    }

    /** @return iterable<string, array{string}> */
    public static function rejectedInputs(): iterable
    {
        yield 'letter o for zero' => ['19o3-12-04 98o2'];
        yield 'full width digits' => ["\u{ff11}\u{ff19}\u{ff18}\u{ff17}"];
        yield 'slash' => ['870101/2384'];
        // The letters Finland excludes from its control-character set, kept out
        // of the allow list for the same reason Finland keeps them out: G, I, O,
        // Q and Z are the ones a reader confuses with 6, 1, 0 and 2.
        yield 'letter Finland excludes, G' => ['870101G384'];
        yield 'letter Finland excludes, Z' => ['870101Z384'];
        // Lowercase, always. The allow list carries Finland's uppercase letters
        // and the normalizer deliberately does not fold case: PHP's
        // mb_strtoupper and JavaScript's toUpperCase disagree for some inputs,
        // and a divergence there is worse than a lowercase Finnish code being
        // reported as InvalidCharacter.
        yield 'lowercase Finnish control character' => ['870101a384'];
        yield 'formerly-folded reservnummer letter, lowercase' => ['870101t384'];
    }
}
