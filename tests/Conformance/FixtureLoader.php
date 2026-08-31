<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Conformance;

use DateTimeImmutable;
use DateTimeZone;
use FilesystemIterator;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class FixtureLoader
{
    private const string FIXTURE_ROOT = __DIR__ . '/../../spec/fixtures';

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function all(): array
    {
        $cases = [];

        foreach (self::files() as $file) {
            /** @var list<array<string, mixed>> $decoded */
            $decoded = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

            foreach ($decoded as $case) {
                $id = self::stringField($case, 'id');

                if (isset($cases[$id])) {
                    throw new RuntimeException("Duplicate fixture id: {$id}");
                }

                $cases[$id] = [$case];
            }
        }

        if ($cases === []) {
            throw new RuntimeException('No fixtures found — the corpus must never be empty.');
        }

        return $cases;
    }

    /**
     * A fixture whose field is missing or mistyped must name itself, not surface
     * later as an unrelated parse failure in whichever suite read it first.
     *
     * @param array<string, mixed> $case
     */
    public static function stringField(array $case, string $key): string
    {
        $value = $case[$key] ?? null;

        if (! is_string($value)) {
            throw new RuntimeException("Fixture field '{$key}' must be a string.");
        }

        return $value;
    }

    /** @param array<string, mixed> $case */
    public static function optionalStringField(array $case, string $key): ?string
    {
        return isset($case[$key]) ? self::stringField($case, $key) : null;
    }

    /**
     * The single reader of a fixture's `options`, shared by ConformanceTest and
     * dump-golden.php. Before this method existed each enumerated its own four
     * flags by hand, so both silently missed `allowSyntheticNumbers` in the same
     * way and agreed with each other on the wrong answer -- see the TypeScript
     * twin, fixtures.ts's parseOptionsFor(), which spreads the flags instead and
     * cannot miss one the same way, but reads from this same single place in
     * spirit: one function per runtime, used by both of that runtime's
     * consumers.
     *
     * @param array<string, mixed> $case
     */
    public static function parseOptions(array $case): ParseOptions
    {
        $referenceDate = self::optionalStringField($case, 'referenceDate');

        /** @var array<string, bool> $flags */
        $flags = $case['options'] ?? [];

        $allowCoordinationNumber = $flags['allowCoordinationNumber'] ?? true;
        $allowOrganizationNumber = $flags['allowOrganizationNumber'] ?? true;
        $allowUnknownBirthNumber = $flags['allowUnknownBirthNumber'] ?? true;
        // Defaults false, mirroring ParseOptions' own restrictive default -- a
        // fixture that wants a synthetic value to parse must opt in explicitly,
        // same as any real caller would.
        $allowSyntheticNumbers = $flags['allowSyntheticNumbers'] ?? false;

        if ($referenceDate === null) {
            return ParseOptions::forCenturyCompleteInput(
                $allowCoordinationNumber,
                $allowOrganizationNumber,
                $allowUnknownBirthNumber,
                $allowSyntheticNumbers,
            );
        }

        return new ParseOptions(
            new DateTimeImmutable($referenceDate, new DateTimeZone('UTC')),
            $allowCoordinationNumber,
            $allowOrganizationNumber,
            $allowUnknownBirthNumber,
            $allowSyntheticNumbers,
        );
    }

    /** @return list<string> */
    private static function files(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::FIXTURE_ROOT, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || $file->getExtension() !== 'json') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }
}
