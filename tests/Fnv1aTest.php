<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Fnv1a;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The vectors are FNV's own published ones. Both runtimes assert the same
 * three, which is what makes a draw reproducible across them: the pool data is
 * byte-identical by codegen, so agreeing on the index is agreeing on the value.
 */
final class Fnv1aTest extends TestCase
{
    #[Test]
    #[DataProvider('publishedVectors')]
    public function itMatchesThePublishedVector(string $value, int $expected): void
    {
        $this->assertSame($expected, Fnv1a::hash($value));
    }

    /** @return array<string, array{string, int}> */
    public static function publishedVectors(): array
    {
        return [
            'empty string' => ['', 0x811C9DC5],
            'a'            => ['a', 0xE40C292C],
            'foobar'       => ['foobar', 0xBF9CF968],
        ];
    }
}
