<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\Generated\TestNumberPoolData;
use OutOfRangeException;

/**
 * Official published test numbers, for seeding and fixtures.
 *
 * A randomly generated valid identity number very likely belongs to a living
 * person, so anything that needs one in a test environment should draw it from
 * here rather than construct it.
 *
 * Read the README beside each range in spec/sources before assuming what a pool
 * guarantees: the Swedish range is reserved and cannot be issued, while
 * Denmark's is assigned last rather than withheld.
 */
final class TestNumbers
{
    /** @return list<string> */
    public static function pool(Scheme $scheme): array
    {
        return TestNumberPoolData::POOLS[$scheme->value]
            ?? throw new OutOfRangeException("no test number pool for {$scheme->value}");
    }

    /**
     * The same key always draws the same number, in both runtimes, so a fixture
     * can be named once and recognised everywhere it appears. Keys are the
     * caller's own — a fixture ref, a scenario name — and nothing here derives
     * one, because a derived key would change whenever its input did.
     */
    public static function draw(Scheme $scheme, string $key): string
    {
        $pool = self::pool($scheme);

        return $pool[Fnv1a::hash($key) % count($pool)];
    }
}
