<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

final class DateValidator
{
    public static function isRealDate(int $year, int $month, int $day): bool
    {
        return checkdate($month, $day, $year);
    }

    /**
     * Zero-padded Y-m-d. Both sides of any comparison use this, so lexical
     * order equals chronological order and no timezone is ever involved.
     */
    public static function iso(int $year, int $month, int $day): string
    {
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
