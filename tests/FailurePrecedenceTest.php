<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The precedence table decides which refusal a caller sees when several schemes
 * reject the same value. Its comment claimed that holding it as data forces a
 * decision about each new member — it did not: the lookup falls back to last
 * place, and adding ImplausibleBirthDate ranked it silently. This is the test
 * that makes the claim true.
 */
final class FailurePrecedenceTest extends TestCase
{
    #[Test]
    public function everyFailureMemberHasADeclaredPrecedence(): void
    {
        /** @var array<string, int> $precedence */
        $precedence = new ReflectionClass(PersonalIdentityNumber::class)
            ->getConstant('FAILURE_PRECEDENCE');

        $unranked = [];

        foreach (ParseFailure::cases() as $failure) {
            if (! array_key_exists($failure->value, $precedence)) {
                $unranked[] = $failure->value;
            }
        }

        $this->assertSame([], $unranked);
    }
}
