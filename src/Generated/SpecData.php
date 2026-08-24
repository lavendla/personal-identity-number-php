<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Generated;

/**
 * Generated from spec/. Do not edit by hand — run `npm run codegen`.
 */
final class SpecData
{
    public const string SPEC_VERSION = '0.3.0';

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
     * @var array<string, string>
     */
    public const array RECOGNIZE_ONLY_SHAPES = [
        'NO' => '[0-9]{11}',
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
}
