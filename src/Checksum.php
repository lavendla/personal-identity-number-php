<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

final class Checksum
{
    /**
     * Returns the check digit the given digits imply. Callers compare it to the
     * digit the number actually carries.
     */
    public static function luhn(string $digits): int
    {
        $sum = 0;

        foreach (str_split($digits) as $position => $digit) {
            $value = (int) $digit * (2 - ($position % 2));
            $sum += $value > 9 ? $value - 9 : $value;
        }

        return (int) (ceil($sum / 10) * 10) - $sum;
    }
}
