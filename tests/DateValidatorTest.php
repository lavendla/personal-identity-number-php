<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests;

use Lavendla\PersonalIdentityNumber\DateValidator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DateValidatorTest extends TestCase
{
    #[Test]
    public function itAcceptsALeapDayInALeapYear(): void
    {
        $this->assertTrue(DateValidator::isRealDate(2024, 2, 29));
    }

    #[Test]
    public function itRejectsALeapDayInANonLeapYear(): void
    {
        $this->assertFalse(DateValidator::isRealDate(2023, 2, 29));
    }

    #[Test]
    public function itRejectsMonthZero(): void
    {
        $this->assertFalse(DateValidator::isRealDate(1987, 0, 1));
    }

    /**
     * Cross-runtime parity: PHP's checkdate() already rejects year 0 (it
     * accepts only 1-32767), but JavaScript's Date has no such floor, so
     * dateValidator.ts needs its own explicit guard. This pins the PHP side
     * of that agreement.
     */
    #[Test]
    public function itRejectsYearZero(): void
    {
        $this->assertFalse(DateValidator::isRealDate(0, 1, 1));
    }
}
