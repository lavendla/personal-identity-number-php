<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Conformance;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * `source` is documented in CLAUDE.md as a fixed vocabulary and was enforced
 * nowhere — a free string in both harnesses. That matters more than it looks:
 * `public-organization` is the marker saying a fixture relies on the
 * organization-number carve-out, so a typo would silently remove the only
 * machine-readable trace of an exception being used.
 */
final class FixtureSourceTest extends TestCase
{
    private const array KNOWN_SOURCES = [
        'skatteverket',
        'medcom',
        'dvv',
        'public-organization',
        'unissuable',
        'constructed',
    ];

    #[Test]
    public function everyFixtureDeclaresAKnownSource(): void
    {
        $unknown = [];

        foreach (FixtureLoader::all() as $id => $case) {
            $source = FixtureLoader::stringField($case[0], 'source');

            if (! in_array($source, self::KNOWN_SOURCES, true)) {
                $unknown[] = "{$id}: {$source}";
            }
        }

        $this->assertSame([], $unknown);
    }

    /**
     * The carve-out is narrow and only Swedish organization numbers may claim
     * it. A Danish or personal-number fixture marked this way would be relying
     * on reasoning that does not cover it.
     */
    #[Test]
    public function onlyOrganizationNumberFixturesClaimTheCarveOut(): void
    {
        $misplaced = [];

        foreach (FixtureLoader::all() as $id => $case) {
            if (FixtureLoader::stringField($case[0], 'source') !== 'public-organization') {
                continue;
            }

            if (! str_contains($id, 'organization-number')) {
                $misplaced[] = $id;
            }
        }

        $this->assertSame([], $misplaced);
    }

    /**
     * The rule this enforces is CLAUDE.md's: a constructed number that happens
     * to be valid is exactly the disclosure risk the corpus rules exist to
     * prevent. It was prose only, and every constructed fixture happening to
     * assert `failed` is not the same thing as the next one having to.
     *
     * A constructed number that must be valid claims Exception 1 and is marked
     * `unissuable` instead, which is what makes the claim visible.
     */
    #[Test]
    public function noConstructedFixtureParses(): void
    {
        $parsing = [];

        foreach (FixtureLoader::all() as $id => $case) {
            $isConstructed = FixtureLoader::stringField($case[0], 'source') === 'constructed';

            if ($isConstructed && FixtureLoader::stringField($case[0], 'outcome') !== 'failed') {
                $parsing[] = $id;
            }
        }

        $this->assertSame([], $parsing);
    }

    /** Exception 1 turns on why the digits cannot have been issued, so the fixture has to say. */
    #[Test]
    public function everyUnissuableFixtureExplainsItself(): void
    {
        $this->assertSame([], $this->unexplainedFixturesFrom('unissuable'));
    }

    /**
     * Exception 3 turns on the individual number being inside the 900-999 block
     * DVV states it does not issue from, and on DVV having published the code
     * itself. Neither is visible in the digits, so the fixture has to say --
     * exactly as `unissuable` has to name the digits the registry cannot issue
     * and `public-organization` has to state its third digit.
     */
    #[Test]
    public function everyDvvFixtureExplainsItself(): void
    {
        $this->assertSame([], $this->unexplainedFixturesFrom('dvv'));
    }

    /**
     * Exception 3 covers Finnish personal identity codes and nothing else, so a
     * fixture from another country marked this way would be leaning on
     * reasoning that does not reach it.
     *
     * Tested against what the fixture *asserts* rather than what its id starts
     * with. The first version of this check required the id to begin `fi-`,
     * which was a proxy and immediately a wrong one: the ambiguity corpus names
     * its files by country pair, so `ambiguous-fi-se-...` is as Finnish as
     * anything in spec/fixtures/fi/ and failed a check that was only ever
     * trying to catch a Danish or Swedish fixture claiming the carve-out.
     */
    #[Test]
    public function onlyFixturesAboutAFinnishCodeClaimTheDvvCarveOut(): void
    {
        $misplaced = [];

        foreach (FixtureLoader::all() as $id => $case) {
            if (FixtureLoader::stringField($case[0], 'source') !== 'dvv') {
                continue;
            }

            if (! $this->namesFinland($case[0])) {
                $misplaced[] = $id;
            }
        }

        $this->assertSame([], $misplaced);
    }

    /** @param array<string, mixed> $case */
    private function namesFinland(array $case): bool
    {
        /** @var list<string> $detected */
        $detected = $case['detected'] ?? [];

        return ($case['issuedBy'] ?? null) === 'FI'
            || ($case['scheme'] ?? null) === 'fi-personal-identity-code'
            || ($case['recognizedCountry'] ?? null) === 'FI'
            || in_array('fi-personal-identity-code', $detected, true);
    }

    /** @return list<string> */
    private function unexplainedFixturesFrom(string $source): array
    {
        $unexplained = [];

        foreach (FixtureLoader::all() as $id => $case) {
            if (FixtureLoader::stringField($case[0], 'source') !== $source) {
                continue;
            }

            if (! array_key_exists('provenanceNote', $case[0])) {
                $unexplained[] = $id;
            }
        }

        return $unexplained;
    }
}
