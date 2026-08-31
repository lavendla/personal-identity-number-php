<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Enums;

enum ParseFailure: string
{
    case NotAnIdentityNumber = 'not-an-identity-number';
    case UnsupportedScheme = 'unsupported-scheme';
    case CountryNotSupported = 'country-not-supported';
    case ChecksumMismatch = 'checksum-mismatch';
    case ImpossibleDate = 'impossible-date';
    case FutureBirthDate = 'future-birth-date';
    case InvalidCharacter = 'invalid-character';
    case SchemeNotAllowed = 'scheme-not-allowed';
    case CenturyRequired = 'century-required';
    case ReferenceDateRequired = 'reference-date-required';

    /**
     * Structurally valid, but the resolved birth year is before the scheme's
     * declared floor. Deliberately distinct from ImpossibleDate: "that date does
     * not exist" and "that date exists but nobody alive was born then" are
     * different information, and a consumer bucketing contaminated data needs to
     * tell them apart.
     */
    case ImplausibleBirthDate = 'implausible-birth-date';

    /**
     * The number carries Skatteetaten/Digdir's `+80` synthetic-month convention
     * used for test data, and `allowSyntheticNumbers` was not set to permit it.
     * A statement about the value, not the request: recognizedCountry is not
     * suppressed for this failure, since the caller may also have named the
     * wrong country.
     */
    case SyntheticNumber = 'synthetic-number';
}
