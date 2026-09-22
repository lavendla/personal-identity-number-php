<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

/**
 * @internal
 *
 * FNV-1a, 32 bit. Chosen over a cryptographic digest because both runtimes have
 * to reach the same answer from nothing but the algorithm — no library, no
 * platform hash, nothing that can differ by version.
 */
final class Fnv1a
{
    private const int OFFSET_BASIS = 0x811C9DC5;
    private const int PRIME = 0x01000193;
    private const int MASK = 0xFFFFFFFF;

    /**
     * Masked on every round rather than at the end: PHP integers are 64 bit and
     * JavaScript's are not, so an unmasked accumulator would diverge from the
     * TypeScript side the first time it passed 2^32.
     */
    public static function hash(string $value): int
    {
        $hash = self::OFFSET_BASIS;
        $length = strlen($value);

        for ($position = 0; $position < $length; $position++) {
            $hash = (($hash ^ ord($value[$position])) * self::PRIME) & self::MASK;
        }

        return $hash;
    }
}
