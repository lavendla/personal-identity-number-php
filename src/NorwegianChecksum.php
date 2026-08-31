<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;

final class NorwegianChecksum
{
    /**
     * Both digits are modulus-11 over weighted sums. A remainder of 0 yields a
     * check digit of 0 rather than 11, and a computed 10 means the registry never
     * issued the number, because a check digit is one digit.
     *
     * Runs on the digits as written. The D-number's +40 day and the synthetic +80
     * month are part of the checksummed value and are removed only afterwards,
     * when a birth date is derived -- see spec/schemes/no/d-number.json.
     */
    public static function matches(string $elevenDigits): bool
    {
        $digits = array_map(intval(...), str_split($elevenDigits));
        $body = array_slice($digits, 0, 9);

        $first = (11 - (self::weighted($body, SpecData::NO_CHECK_DIGIT_WEIGHTS['first']) % 11)) % 11;

        if ($first === 10 || $first !== $digits[9]) {
            return false;
        }

        $second = (11 - (self::weighted([...$body, $first], SpecData::NO_CHECK_DIGIT_WEIGHTS['second']) % 11)) % 11;

        return $second !== 10 && $second === $digits[10];
    }

    /**
     * @param list<int> $digits
     * @param list<int> $weights
     */
    private static function weighted(array $digits, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += $weight * ($digits[$index] ?? 0);
        }

        return $sum;
    }
}
