<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * A real calendar birth date, already stripped of any encoding quirk. A Swedish
 * coordination number's +60 day offset is removed by the scheme before this is
 * built: the offset describes the number, not the person's birthday.
 */
final readonly class BirthDate
{
    /**
     * The guard is not redundant with the schemes, which all validate before
     * constructing. toDateTime() builds with setDate(), which rolls an
     * impossible date forward instead of refusing it, so an unchecked
     * BirthDate(2024, 2, 30) answered 2024-02-30 from iso() and 2024-03-01
     * from toDateTime(). An invariant held only by convention is one a later
     * caller breaks silently, and a silently wrong birth date is the worst
     * thing this package can produce.
     *
     * This exception is a programming error, never a parse outcome. Callers
     * see ParseFailure members; nothing here reaches them.
     */
    public function __construct(
        public int $year,
        public int $month,
        public int $day,
    ) {
        if (! DateValidator::isRealDate($year, $month, $day)) {
            throw new InvalidArgumentException('BirthDate requires a real calendar date.');
        }
    }

    public function iso(): string
    {
        return DateValidator::iso($this->year, $this->month, $this->day);
    }

    /**
     * Built with setDate() rather than createFromFormat(), which returns false
     * on input it cannot parse and would force this to return a nullable a
     * caller can never receive: a BirthDate is only ever constructed from
     * components a scheme already accepted via DateValidator::isRealDate().
     * setDate() is total, so the guarantee lives in the type instead of in a
     * comment promising the null is unreachable.
     */
    public function toDateTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('1970-01-01', new DateTimeZone('UTC'))
            ->setDate($this->year, $this->month, $this->day);
    }
}
