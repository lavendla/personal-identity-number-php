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

    /**
     * A typo in spec/error-codes.json's requestLevelFailures would otherwise
     * be caught by nothing: century-required and scheme-not-allowed are
     * covered by fixture assertions, but reference-date-required is
     * unreachable from explain()/parse() today, so no fixture could ever
     * notice it silently degrading to a value ParseFailure does not have.
     */
    #[Test]
    public function everyRequestLevelFailureIsARealParseFailureValue(): void
    {
        $wireValues = array_map(
            static fn(ParseFailure $failure): string => $failure->value,
            ParseFailure::cases(),
        );

        foreach (SpecData::REQUEST_LEVEL_FAILURES as $failure) {
            $this->assertContains($failure, $wireValues);
        }
    }
}
