<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnumParityTest extends TestCase
{
    #[Test]
    public function parseFailureMatchesTheSpecErrorCodes(): void
    {
        $members = array_map(
            static fn(ParseFailure $failure): string => $failure->name,
            ParseFailure::cases(),
        );

        $this->assertSame(SpecData::FAILURES, $members);
    }
}
