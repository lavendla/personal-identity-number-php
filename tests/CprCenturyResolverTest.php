<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\CprCenturyResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CprCenturyResolverTest extends TestCase
{
    /**
     * One case per row of CPR's published table.
     *
     * The 58-99 rows are the counterintuitive ones and are why this test
     * exists: serials 5000-8999 for those years are reserved for nineteenth
     * century births, so a serial of 8967 with year 75 is a person born in
     * 1875, not 1975. The design's own worked example uses exactly that.
     * Anyone "correcting" these to the intuitive answer has broken the table.
     *
     * @return list<array{int, int, int}>
     */
    public static function tableRows(): array
    {
        return [
            [75, 3000, 1975],
            [36, 4500, 2036],
            [37, 4500, 1937],
            [57, 6000, 2057],
            [58, 6000, 1858],
            [75, 8967, 1875],
            [36, 9500, 2036],
            [37, 9500, 1937],
        ];
    }

    #[Test]
    #[DataProvider('tableRows')]
    public function resolvesAgainstThePublishedTable(int $twoDigitYear, int $serial, int $expected): void
    {
        $this->assertSame($expected, CprCenturyResolver::resolve($twoDigitYear, $serial));
    }

    #[Test]
    public function serialZeroResolvesToNothing(): void
    {
        $this->assertNull(CprCenturyResolver::resolve(75, 0));
    }
}
