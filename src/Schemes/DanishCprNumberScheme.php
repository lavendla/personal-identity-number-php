<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\BirthTime;
use Lavendla\PersonalIdentityNumber\CprCenturyResolver;
use Lavendla\PersonalIdentityNumber\DateValidator;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\SchemeResult;

final class DanishCprNumberScheme
{
    private const string SHAPE = '/^(?<day>[0-9]{2})(?<month>[0-9]{2})(?<year>[0-9]{2})'
        . '(?<separator>-)?(?<serial>[0-9]{4})$/';

    /**
     * There is no checksum. Modulus-11 was abandoned by CPR in 2007 and its own
     * documentation states that numbers assigned without it are fully valid, so
     * enforcing it would reject real people. The consequence is that a
     * single-digit typo in the serial is undetectable: it silently resolves to
     * a different century rather than failing. See spec/schemes/dk/cpr-number.json.
     *
     * No allowCoordinationNumber parameter, because Denmark has no such scheme.
     * The dispatcher passes each scheme what that scheme actually uses.
     */
    public static function parse(string $normalized, ?DateTimeImmutable $referenceDate): SchemeResult|ParseFailure
    {
        if (preg_match(self::SHAPE, $normalized, $matches) !== 1) {
            return ParseFailure::NotAnIdentityNumber;
        }

        $serial = (int) $matches['serial'];
        $year = CprCenturyResolver::resolve((int) $matches['year'], $serial);

        // A serial of 0000 falls outside every row of the published table, so
        // the lookup rejects it without a separate range check.
        if ($year === null) {
            return ParseFailure::NotAnIdentityNumber;
        }

        $month = (int) $matches['month'];
        $day = (int) $matches['day'];

        // After the century lookup, never before: whether 29 February exists
        // depends on which century the serial resolved to.
        if (! DateValidator::isRealDate($year, $month, $day)) {
            return ParseFailure::ImpossibleDate;
        }

        if ($referenceDate !== null && DateValidator::iso($year, $month, $day) > $referenceDate->format('Y-m-d')) {
            return ParseFailure::FutureBirthDate;
        }

        return new SchemeResult(
            Scheme::DkCprNumber,
            $matches['day'] . $matches['month'] . $matches['year'] . $matches['serial'],
            new BirthTime($year, $month, $day),
            $serial % 2 === 1 ? Gender::Male : Gender::Female,
        );
    }
}
