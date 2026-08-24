<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use DateTimeImmutable;
use DateTimeZone;

final readonly class ParseOptions
{
    public ?DateTimeImmutable $referenceDate;

    /**
     * Normalizes $referenceDate to UTC on the way in. TypeScript's `Date`
     * has no timezone concept and always reads the UTC calendar day of an
     * instant; PHP's `DateTimeImmutable` carries whatever timezone the
     * caller attached and every downstream `->format('Y-m-d')`
     * (`CenturyResolver`, `SwedishPersonalNumberScheme`) reads that zone
     * verbatim. Normalizing here, once, means the rest of the package can
     * trust the reference date already reads the same calendar day the
     * paired TypeScript runtime would read for the same instant.
     */
    public function __construct(
        ?DateTimeImmutable $referenceDate,
        public bool $allowCoordinationNumber = true,
        public bool $allowOrganizationNumber = true,
        public bool $allowUnknownBirthNumber = true,
    ) {
        $this->referenceDate = $referenceDate?->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * For input already carrying an explicit century, where no reference date
     * is meaningful. Parsing century-incomplete input with these options fails
     * with ParseFailure::CenturyRequired rather than guessing.
     */
    public static function forCenturyCompleteInput(
        bool $allowCoordinationNumber = true,
        bool $allowOrganizationNumber = true,
        bool $allowUnknownBirthNumber = true,
    ): self {
        return new self(
            null,
            $allowCoordinationNumber,
            $allowOrganizationNumber,
            $allowUnknownBirthNumber,
        );
    }
}
