<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

/**
 * What a number's date digits say about the bearer's birth — Skatteverket's
 * word "födelsetid", the six digits the check digit is computed from. The year is
 * always present. The month and the day are null when the number declares them
 * unknown, which a Swedish coordination number does with a month of `00` and a
 * day of `00` written as `60`, the offset applied.
 *
 * Deliberately not the same thing as BirthDate, which is a real calendar date
 * and refuses to be anything else. A partial birth time never becomes one and
 * birthDate() reports null for it — but the year stays available, which the
 * short form's +/- separator needs even when the rest is missing. This is also
 * why the constructor does not validate: isPossible() answers that question,
 * because a scheme has to be able to hold the digits before deciding they are
 * refused.
 */
final readonly class BirthTime
{
    public function __construct(
        public int $year,
        public ?int $month,
        public ?int $day,
    ) {}

    public function isComplete(): bool
    {
        return $this->month !== null && $this->day !== null;
    }

    public function toBirthDate(): ?BirthDate
    {
        if ($this->month === null || $this->day === null) {
            return null;
        }

        return new BirthDate($this->year, $this->month, $this->day);
    }

    public function isPossible(): bool
    {
        return DateValidator::isRealDate($this->year, $this->earliestMonth(), $this->earliestDay());
    }

    public function earliestPossibleIso(): string
    {
        return DateValidator::iso($this->year, $this->earliestMonth(), $this->earliestDay());
    }

    /**
     * The earliest date the digits allow: an unknown month reads as January and
     * an unknown day as the 1st. Three questions need exactly this, and each
     * would answer differently on its own.
     *
     * Century resolution asks whether the bearer could have been born in the
     * candidate century, so it must only step back when even the earliest
     * possible date is after the reference. isPossible() asks whether what is
     * known is internally consistent, and January is what makes "is the day
     * within 1-31" the right question for an unknown month, because January has
     * 31 days. The future-birth check asks whether the bearer cannot yet exist at
     * all — for which the earliest date is again the only honest candidate.
     */
    private function earliestMonth(): int
    {
        return $this->month ?? 1;
    }

    private function earliestDay(): int
    {
        return $this->day ?? 1;
    }
}
