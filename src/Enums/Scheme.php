<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Enums;

enum Scheme: string
{
    case SePersonalNumber = 'se-personal-number';
    case SeCoordinationNumber = 'se-coordination-number';
    case SeOrganizationNumber = 'se-organization-number';
    case DkCprNumber = 'dk-cpr-number';

    public function country(): Country
    {
        return match ($this) {
            self::SePersonalNumber,
            self::SeCoordinationNumber,
            self::SeOrganizationNumber => Country::Sweden,
            self::DkCprNumber          => Country::Denmark,
        };
    }

    public function isPerson(): bool
    {
        return $this !== self::SeOrganizationNumber;
    }

    /**
     * How many leading characters Format::Display drops from the canonical form.
     *
     * Only organization numbers drop any. Their `16` is a legal-person marker
     * rather than data about the entity, and the form Swedish organization
     * numbers are actually written in — `202100-5448` — does not show it.
     * Nothing is lost: `canonical()` still carries it, and that is the value
     * applications index on.
     */
    public function displayElision(): int
    {
        return $this === self::SeOrganizationNumber ? 2 : 0;
    }

    /**
     * Where Format::Display inserts its separator, counted after any elision.
     *
     * Sweden's personal and coordination numbers write CCYYMMDD and split after
     * eight. Denmark writes DDMMYY and splits after six. An organization number
     * splits after six too, having already dropped its prefix.
     */
    public function displaySplit(): int
    {
        return match ($this) {
            self::SePersonalNumber, self::SeCoordinationNumber => 8,
            self::SeOrganizationNumber, self::DkCprNumber      => 6,
        };
    }

    /**
     * Whether Format::Short elides a century, which is the only reason it ever
     * needs a reference date: the `+`/`-` separator reports whether the bearer
     * has turned 100 at that date.
     *
     * True only for Sweden's personal and coordination numbers. Denmark's
     * ten-digit form carries no century, and an organization number has no
     * bearer and no age — so for both, Short is Display and no reference date is
     * required. An earlier version asked `carriesCentury()`, which answered true
     * for organization numbers because their `16` occupies the same two
     * positions, and made Format::Short throw ReferenceDateRequired for a value
     * that has no age to report.
     */
    public function shortFormElidesCentury(): bool
    {
        return $this === self::SePersonalNumber || $this === self::SeCoordinationNumber;
    }
}
