<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SpecVersionTest extends TestCase
{
    #[Test]
    public function itReportsTheVersionFromTheSpecDirectory(): void
    {
        $expected = trim((string) file_get_contents(__DIR__ . '/../spec/VERSION'));

        $this->assertSame($expected, SpecData::SPEC_VERSION);
    }
}
