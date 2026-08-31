<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Enums;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use OutOfRangeException;

enum Scheme: string
{
    case SePersonalNumber = 'se-personal-number';
    case SeCoordinationNumber = 'se-coordination-number';
    case SeOrganizationNumber = 'se-organization-number';
    case DkCprNumber = 'dk-cpr-number';
    case NoNationalIdentityNumber = 'no-national-identity-number';
    case NoDNumber = 'no-d-number';
    case FiPersonalIdentityCode = 'fi-personal-identity-code';

    public function country(): Country
    {
        return Country::from($this->metadata()['country']);
    }

    public function isPerson(): bool
    {
        return $this->metadata()['isPerson'];
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
        return $this->metadata()['displayElision'];
    }

    /**
     * Where Format::Display inserts its separator, counted after any elision,
     * or null when the canonical form already carries one.
     *
     * Sweden's personal and coordination numbers write CCYYMMDD and split after
     * eight. Denmark writes DDMMYY and splits after six. An organization number
     * splits after six too, having already dropped its prefix.
     *
     * Null only for Finland, and it is a real answer rather than a missing one:
     * a Finnish code's intermediate character sits at exactly the position a
     * separator would be inserted at, and decree 690/2022 makes it part of the
     * identity, so there is nothing to insert. See
     * spec/schemes/fi/personal-identity-code.json's displaySplitNote.
     */
    public function displaySplit(): ?int
    {
        return $this->metadata()['displaySplit'];
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
     *
     * Norway and Finland are false for the same reason as Denmark, by a
     * different route: Norway's century lives in the individnummer rather than a
     * separate marker, and Finland's century marker is load-bearing under decree
     * 690/2022, so neither is ever elided from any written form.
     */
    public function shortFormElidesCentury(): bool
    {
        return $this->metadata()['shortFormElidesCentury'];
    }

    /**
     * Every accessor above reads from here rather than matching on $this, so a
     * scheme added to spec/schemes/ without a SCHEME_METADATA entry fails
     * loudly here instead of a match expression silently missing an arm.
     *
     * @return array{
     *     country: string,
     *     displayElision: int,
     *     displaySplit: int|null,
     *     isPerson: bool,
     *     shortFormElidesCentury: bool,
     * }
     */
    private function metadata(): array
    {
        if (! array_key_exists($this->value, SpecData::SCHEME_METADATA)) {
            throw new OutOfRangeException("no scheme metadata for {$this->value}");
        }

        return SpecData::SCHEME_METADATA[$this->value];
    }
}
