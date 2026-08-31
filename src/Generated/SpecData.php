<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Generated;

/**
 * Generated from spec/. Do not edit by hand — run `npm run codegen`.
 */
final class SpecData
{
    public const string SPEC_VERSION = '1.0.0';

    /** @var array<string, string> */
    public const array FOLD = [
        ' ' => '',
        ' ' => '',
        ' ' => '',
        ' ' => '',
        '–' => '-',
        '—' => '-',
        '−' => '-',
    ];

    /** @var list<string> */
    public const array ALLOWED = [
        '0',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '-',
        '+',
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'H',
        'J',
        'K',
        'L',
        'M',
        'N',
        'P',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
    ];

    /** @var list<string> */
    public const array FAILURES = [
        'NotAnIdentityNumber',
        'UnsupportedScheme',
        'CountryNotSupported',
        'ChecksumMismatch',
        'ImpossibleDate',
        'FutureBirthDate',
        'InvalidCharacter',
        'SchemeNotAllowed',
        'CenturyRequired',
        'ReferenceDateRequired',
        'ImplausibleBirthDate',
        'SyntheticNumber',
    ];

    /**
     * The ParseFailure wire values that describe the request rather than the
     * value -- see spec/error-codes.json's requestLevelFailuresNote. The
     * dispatcher suppresses recognizedCountry when the winning failure is one
     * of these: a value that would have parsed given different options is not
     * evidence the caller named the wrong country.
     *
     * @var list<string>
     */
    public const array REQUEST_LEVEL_FAILURES = [
        'century-required',
        'scheme-not-allowed',
        'reference-date-required',
    ];

    /**
     * Earliest plausible birth year per scheme, keyed by scheme id. A resolved
     * year below the floor fails with ImplausibleBirthDate.
     *
     * @var array<string, int>
     */
    public const array MINIMUM_BIRTH_YEARS = [
        'se-personal-number' => 1800,
        'dk-cpr-number'      => 1800,
    ];

    /**
     * Shapes a recognize-only scheme matches, keyed by country code, in the
     * order they are tried. Unanchored: the scheme anchors them.
     *
     * Superseded as CountryRecognizer's data source by COUNTRY_SHAPES below,
     * which covers every country rather than only the recognize-only ones.
     * Kept as its own constant because README.md §12 step 1 requires the next
     * country to ship through this tier first, on its own -- deleting the
     * on-ramp because nothing reads it today is how that step gets skipped.
     *
     * @var array<string, string>
     */
    public const array RECOGNIZE_ONLY_SHAPES = [

    ];

    /**
     * Every scheme's recognitionPattern, keyed by country code and combined
     * with alternation where a country has more than one scheme. Unanchored:
     * the recognizer anchors it, wrapping the whole value in a group first so
     * an alternation cannot leak past the anchors.
     *
     * @var array<string, string>
     */
    public const array COUNTRY_SHAPES = [
        'SE' => '(?:(?:[0-9]{2})?[0-9]{6}[-+]?[0-9]{4})|(?:(?:16)?[0-9]{6}-?[0-9]{4})',
        'DK' => '[0-9]{6}-?[0-9]{4}',
        'NO' => '(?:[0-9]{11})|(?:[0-9]{11})',
        'FI' => '[0-9]{6}[-+ABCDEFUVWXY][0-9]{3}[0-9ABCDEFHJKLMNPRSTUVWXY]',
    ];

    /**
     * The digit strings a Swedish number uses to declare a field unknown, and
     * the one four-digit tail exempt from the checksum. Declared in
     * spec/schemes/se/personal-number.json, where each carries its reasoning.
     *
     * @var array<string, string>
     */
    public const array SE_PARTIAL_IDENTITY = [
        'checksumExemptTail' => '0000',
        'unknownBirthNumber' => '000',
        'unknownDayEncoded'  => '60',
        'unknownMonth'       => '00',
    ];

    /**
     * CPR's published century table. A serial and two-digit year matching a row
     * resolve to centuryBase + year. Total for every serial 0001-9999; a serial
     * of 0000 matches no row, which is how DDMMYY-0000 is rejected.
     *
     * @var list<array{serialMinimum: int, serialMaximum: int, yearMinimum: int, yearMaximum: int, centuryBase: int}>
     */
    public const array DK_CENTURY_TABLE = [
        [
            'serialMinimum' => 1,
            'serialMaximum' => 3999,
            'yearMinimum'   => 0,
            'yearMaximum'   => 99,
            'centuryBase'   => 1900,
        ],
        [
            'serialMinimum' => 4000,
            'serialMaximum' => 4999,
            'yearMinimum'   => 0,
            'yearMaximum'   => 36,
            'centuryBase'   => 2000,
        ],
        [
            'serialMinimum' => 4000,
            'serialMaximum' => 4999,
            'yearMinimum'   => 37,
            'yearMaximum'   => 99,
            'centuryBase'   => 1900,
        ],
        [
            'serialMinimum' => 5000,
            'serialMaximum' => 8999,
            'yearMinimum'   => 0,
            'yearMaximum'   => 57,
            'centuryBase'   => 2000,
        ],
        [
            'serialMinimum' => 5000,
            'serialMaximum' => 8999,
            'yearMinimum'   => 58,
            'yearMaximum'   => 99,
            'centuryBase'   => 1800,
        ],
        [
            'serialMinimum' => 9000,
            'serialMaximum' => 9999,
            'yearMinimum'   => 0,
            'yearMaximum'   => 36,
            'centuryBase'   => 2000,
        ],
        [
            'serialMinimum' => 9000,
            'serialMaximum' => 9999,
            'yearMinimum'   => 37,
            'yearMaximum'   => 99,
            'centuryBase'   => 1900,
        ],
    ];

    /**
     * Norway's modulus-11 check-digit weights, shared by fødselsnummer and
     * D-nummer -- both compute the same pair over the eleven digits as
     * written, D-nummer included, since the +40 day offset is removed only
     * when deriving a birth date, never before the checksum. A result of 11
     * becomes 0; a result of 10 means the registry never issued the number.
     * Declared once, on spec/schemes/no/national-identity-number.json, and
     * read from there for both schemes.
     *
     * @var array{first: list<int>, second: list<int>}
     */
    public const array NO_CHECK_DIGIT_WEIGHTS = [
        'first'  => [3, 7, 6, 1, 8, 9, 4, 5, 2],
        'second' => [5, 4, 3, 2, 7, 6, 5, 4, 3, 2],
    ];

    /**
     * Individnummer-to-century tables, keyed by scheme id. Fødselsnummer and
     * D-nummer do NOT share a table -- Folkeregisterhåndboken §§ 2-2-1 and
     * 2-2-2 give them different series, and individnummer 600 with a
     * two-digit year of 70 resolves to 1870 under one and a post-2000 birth
     * under the other. Neither table is total: an individnummer/year pair
     * matching no row means the registry cannot have issued that number.
     *
     * @var array<string, list<array{
     *     individualMinimum: int,
     *     individualMaximum: int,
     *     yearMinimum: int,
     *     yearMaximum: int,
     *     centuryBase: int,
     * }>>
     */
    public const array NO_CENTURY_ROWS = [
        'no-national-identity-number' => [
            [
                'individualMinimum' => 0,
                'individualMaximum' => 499,
                'yearMinimum'       => 0,
                'yearMaximum'       => 99,
                'centuryBase'       => 1900,
            ],
            [
                'individualMinimum' => 500,
                'individualMaximum' => 749,
                'yearMinimum'       => 54,
                'yearMaximum'       => 99,
                'centuryBase'       => 1800,
            ],
            [
                'individualMinimum' => 500,
                'individualMaximum' => 999,
                'yearMinimum'       => 0,
                'yearMaximum'       => 39,
                'centuryBase'       => 2000,
            ],
            [
                'individualMinimum' => 900,
                'individualMaximum' => 999,
                'yearMinimum'       => 40,
                'yearMaximum'       => 99,
                'centuryBase'       => 1900,
            ],
        ],
        'no-d-number' => [
            [
                'individualMinimum' => 0,
                'individualMaximum' => 499,
                'yearMinimum'       => 0,
                'yearMaximum'       => 99,
                'centuryBase'       => 1900,
            ],
            [
                'individualMinimum' => 500,
                'individualMaximum' => 999,
                'yearMinimum'       => 0,
                'yearMaximum'       => 99,
                'centuryBase'       => 2000,
            ],
        ],
    ];

    /**
     * Added to the day-of-month digits of a D-nummer, so day 1 is written 41
     * and day 31 is written 71. Removed only when deriving a birth date --
     * never before the modulus-11 checksum, which is computed over the
     * D-nummer as written.
     */
    public const int NO_DAY_OFFSET = 40;

    /**
     * Added to the month digits of a Skatteetaten Tenor synthetic test
     * number, so a real month of 1-12 is written 81-92 -- a month no bearer
     * can have. Check digits are computed after this offset is applied, so a
     * synthetic number is modulus-11 valid. Gated by allowSyntheticNumbers,
     * which defaults to false.
     */
    public const int NO_SYNTHETIC_MONTH_OFFSET = 80;

    /**
     * Finland's modulus-31 control alphabet, indexed by the remainder of the
     * nine preceding digits divided by 31. G, I, O, Q and Z are absent by
     * design, to stay legible against 6, 1, 0 and 2 -- so the string is
     * thirty-one characters and not the thirty-six an alphanumeric run would
     * give. Declared in spec/schemes/fi/personal-identity-code.json.
     */
    public const string FI_CONTROL_ALPHABET = '0123456789ABCDEFHJKLMNPRSTUVWXY';

    /**
     * Intermediate character to century base, from valtioneuvoston asetus
     * 690/2022. Inverted at codegen from the decree's own century-to-markers
     * grouping, so neither runtime holds an opinion about which letter belongs
     * to which century. A marker absent from this map was never assigned by
     * the decree and means the value is not a Finnish code at all.
     *
     * @var array<string, int>
     */
    public const array FI_CENTURY_MARKERS = [
        '+' => 1800,
        '-' => 1900,
        'Y' => 1900,
        'X' => 1900,
        'W' => 1900,
        'V' => 1900,
        'U' => 1900,
        'A' => 2000,
        'B' => 2000,
        'C' => 2000,
        'D' => 2000,
        'E' => 2000,
        'F' => 2000,
    ];

    /**
     * Per-scheme display and classification metadata, keyed by scheme id. The
     * source of truth for Scheme::country(), isPerson(), displaySplit(),
     * displayElision() and shortFormElidesCentury() -- generated so a scheme
     * added to spec/schemes/ without an entry here fails loudly instead of
     * falling through an else-branch. Sweden's coordination number has no file
     * of its own; its entry is read from se/personal-number.json's
     * coordinationScheme block.
     *
     * @var array<string, array{
     *     country: string,
     *     displayElision: int,
     *     displaySplit: int|null,
     *     isPerson: bool,
     *     shortFormElidesCentury: bool,
     * }>
     */
    public const array SCHEME_METADATA = [
        'se-personal-number' => [
            'country'                => 'SE',
            'displayElision'         => 0,
            'displaySplit'           => 8,
            'isPerson'               => true,
            'shortFormElidesCentury' => true,
        ],
        'se-coordination-number' => [
            'country'                => 'SE',
            'displayElision'         => 0,
            'displaySplit'           => 8,
            'isPerson'               => true,
            'shortFormElidesCentury' => true,
        ],
        'se-organization-number' => [
            'country'                => 'SE',
            'displayElision'         => 2,
            'displaySplit'           => 6,
            'isPerson'               => false,
            'shortFormElidesCentury' => false,
        ],
        'dk-cpr-number' => [
            'country'                => 'DK',
            'displayElision'         => 0,
            'displaySplit'           => 6,
            'isPerson'               => true,
            'shortFormElidesCentury' => false,
        ],
        'no-national-identity-number' => [
            'country'                => 'NO',
            'displayElision'         => 0,
            'displaySplit'           => 6,
            'isPerson'               => true,
            'shortFormElidesCentury' => false,
        ],
        'no-d-number' => [
            'country'                => 'NO',
            'displayElision'         => 0,
            'displaySplit'           => 6,
            'isPerson'               => true,
            'shortFormElidesCentury' => false,
        ],
        'fi-personal-identity-code' => [
            'country'                => 'FI',
            'displayElision'         => 0,
            'displaySplit'           => null,
            'isPerson'               => true,
            'shortFormElidesCentury' => false,
        ],
    ];
}
