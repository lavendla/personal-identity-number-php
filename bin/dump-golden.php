#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Runs the full fixture corpus through explain() and prints a deterministic
 * JSON dump to stdout, keyed by fixture id in sorted order.
 *
 * This is one half of the golden snapshot gate (see tools/compare-golden.mjs
 * and spec/golden.json). Its TypeScript twin, packages/js/bin/dumpGolden.ts,
 * must produce byte-identical output for the same fixture corpus — that is
 * what proves the two runtimes agree.
 */

require __DIR__ . '/../vendor/autoload.php';

use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\Format;
use Lavendla\PersonalIdentityNumber\Exceptions\ParseException;
use Lavendla\PersonalIdentityNumber\ParseOutcome;
use Lavendla\PersonalIdentityNumber\PersonalIdentityNumber;
use Lavendla\PersonalIdentityNumber\Tests\Conformance\FixtureLoader;

/** Every rendering the dump asks for, in the fixed order they are emitted. */
const GOLDEN_FORMAT_KEYS = ['canonical', 'display', 'masked', 'short'];

/** @param array<string, mixed> $case */
function golden_explain(array $case): ParseOutcome
{
    // An absent issuedBy means no country is named, so every scheme runs. That
    // is how an ambiguity fixture asks its question.
    $issuedBy = FixtureLoader::optionalStringField($case, 'issuedBy');

    return PersonalIdentityNumber::explain(
        FixtureLoader::stringField($case, 'input'),
        $issuedBy === null ? null : Country::from($issuedBy),
        FixtureLoader::parseOptions($case),
    );
}

/**
 * Renders every Format for a resolved number. `Format::Short` throws
 * ReferenceDateRequired when the number carries no reference date — that is
 * data about the fixture, not a dump failure, so it is recorded as
 * `{"error": "reference-date-required"}` in place of the rendering rather
 * than letting the dump crash or silently skipping the fixture.
 *
 * @return array<string, string|array<string, string>>
 */
function golden_render_formats(PersonalIdentityNumber $number): array
{
    $formats = [];

    foreach (GOLDEN_FORMAT_KEYS as $formatValue) {
        $format = Format::from($formatValue);

        try {
            $formats[$formatValue] = $number->format($format);
        } catch (ParseException $exception) {
            $formats[$formatValue] = ['error' => $exception->getFailure()->value];
        }
    }

    return $formats;
}

/**
 * @param array<string, mixed> $case
 * @return array<string, mixed>
 */
function golden_dump_case(array $case): array
{
    $outcome = golden_explain($case);

    if ($outcome->isAmbiguous()) {
        return [
            'canonical'         => null,
            'failure'           => null,
            'formats'           => null,
            'outcome'           => 'ambiguous',
            'recognizedCountry' => null,
            'scheme'            => null,
        ];
    }

    $number = $outcome->number();

    if ($number === null) {
        // The recognized country is dumped, not merely asserted by a fixture.
        // A recognize-only scheme's whole output is this field, so leaving it out
        // would let the two runtimes disagree about which country matched while
        // golden:check stayed green — the trap docs/open-threads.md §3.17
        // records three times over.
        return [
            'canonical'         => null,
            'failure'           => $outcome->failure()?->value,
            'formats'           => null,
            'outcome'           => 'failed',
            'recognizedCountry' => $outcome->recognizedCountry()?->value,
            'scheme'            => null,
        ];
    }

    return [
        'canonical'         => $number->canonical(),
        'failure'           => null,
        'formats'           => golden_render_formats($number),
        'outcome'           => 'parsed',
        'recognizedCountry' => null,
        'scheme'            => $number->scheme()->value,
    ];
}

/**
 * A hand-rolled encoder rather than `json_encode(..., JSON_PRETTY_PRINT)`.
 * Both runtimes' pretty-printers exist to make the file readable, but their
 * default indentation and spacing conventions differ from each other and
 * from any given JS engine. This gate requires the PHP and TypeScript dumps
 * to be byte-identical, so both scripts implement this exact algorithm
 * instead of trusting two unrelated libraries to agree by coincidence.
 */
function golden_encode(mixed $value, int $level = 0): string
{
    if ($value === null) {
        return 'null';
    }

    if (is_string($value)) {
        return golden_encode_string($value);
    }

    if (is_array($value)) {
        if ($value === []) {
            return '{}';
        }

        $indent = str_repeat('    ', $level + 1);
        $closingIndent = str_repeat('    ', $level);
        $lines = [];

        foreach ($value as $key => $item) {
            $lines[] = $indent . golden_encode_string((string) $key) . ': ' . golden_encode($item, $level + 1);
        }

        return "{\n" . implode(",\n", $lines) . "\n{$closingIndent}}";
    }

    throw new RuntimeException('Unsupported golden value type: ' . get_debug_type($value));
}

function golden_encode_string(string $value): string
{
    $length = strlen($value);
    $result = '"';

    for ($i = 0; $i < $length; $i++) {
        $char = $value[$i];
        $code = ord($char);

        $result .= match (true) {
            $char === '"'  => '\\"',
            $char === '\\' => '\\\\',
            $char === "\n" => '\\n',
            $char === "\r" => '\\r',
            $char === "\t" => '\\t',
            $code < 0x20   => sprintf('\\u%04x', $code),
            default        => $char,
        };
    }

    return $result . '"';
}

$cases = FixtureLoader::all();
$ids = array_keys($cases);
sort($ids, SORT_STRING);

$golden = [];

foreach ($ids as $id) {
    $golden[$id] = golden_dump_case($cases[$id][0]);
}

echo golden_encode($golden) . "\n";
