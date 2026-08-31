<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use Lavendla\PersonalIdentityNumber\ParseOptions;
use Lavendla\PersonalIdentityNumber\SchemeResult;
use Lavendla\PersonalIdentityNumber\SupportedSchemes;

/**
 * Which country a caller who guessed wrong should try instead, or null.
 *
 * Two tiers, deliberately different, because they answer different
 * questions. For a country that **has a scheme**, this asks "would that
 * country's own registry actually accept this number" — it runs the real
 * scheme, permissively, and reports the country only for a genuine
 * SchemeResult. For a country that has **only a shape** in spec/schemes/,
 * there is no real scheme to ask, so it falls back to a shape match against
 * SpecData::COUNTRY_SHAPES: digit counts and separator positions, nothing
 * more.
 *
 * **Every country has a scheme today, so the second tier runs over an empty
 * list.** It is kept, and kept tested by
 * recognizeOnlyShapesStillNamesNorwayAndFinland(), because README.md section
 * 12 step 1 requires the next country to ship recognize-only first — it will
 * arrive with a Country member and a recognitionPattern and no scheme, and
 * that is the state this tier exists for. Deleting it because nothing reaches
 * it today is how that step gets skipped.
 *
 * The two tiers used to be one. Testing shape alone for every country looked
 * uniform, but Sweden's shape (six-to-twelve digits, optional separator)
 * matches essentially anything the real length of a Nordic identity number
 * — measured, 100% of ten-digit strings — where Luhn cuts that to 10% and a
 * real calendar date to 0.4%. A hint that fires on 100% of candidates is not
 * a hint, and reporting one for a value the named country's own scheme would
 * reject outright is worse than reporting nothing: "advisory" means "less
 * certain than a parse", not "unconstrained by one". Norway moved to the
 * real-parse tier once its fødselsnummer scheme was wired up and Finland
 * when its own was, both ahead of `support: "full"` in their spec files —
 * see those files' supportNotes for why the two are allowed to move
 * separately.
 *
 * A real parse now outranks a shape match across countries, not only within
 * one: the tiers run as two passes rather than interleaved in
 * SpecData::COUNTRY_SHAPES order, so a country asked for real and accepting
 * always wins over a later country that merely shape-matches. That was not
 * observable while the tiers were interleaved and every country was on the
 * first one; it becomes observable the moment a recognize-only country is
 * added, and this is the ordering that follows from "advisory means less
 * certain than a parse".
 *
 * A parse always outranks a recognition regardless of tier. The dispatcher
 * only reaches this class once every scheme it consulted has already
 * refused the value, so the result is advisory metadata attached to a
 * failure — never a candidate, never a substitute for a parse, and never
 * proof that a bearer exists even where a real scheme accepted the shape:
 * Sweden accepting `0104909989` as a coordination number under permissive
 * options says only that the *shape and date* are plausible, not that a real
 * Swedish bearer holds it.
 */
final class CountryRecognizer
{
    /**
     * `$excluding` is countries already consulted, tested first so a
     * matching country among them is skipped rather than returned and then
     * discarded — the dispatcher passes this so "Sweden refused it and it
     * looks Swedish" is never reported, and so that a country actually
     * worth reporting is not lost behind an excluded one matching first in
     * SpecData::COUNTRY_SHAPES order.
     *
     * `$referenceDate` and `$allowSyntheticNumbers` are the caller's own,
     * passed through unchanged. The permissive attempt below relaxes the
     * caller-narrowing allow* flags — allowCoordinationNumber,
     * allowOrganizationNumber, allowUnknownBirthNumber — because
     * SchemeNotAllowed is already a request-level failure the dispatcher
     * suppresses (see spec/error-codes.json's requestLevelFailures), and the
     * registry has no opinion on what the caller's own request happens to
     * exclude. `$referenceDate` and `$allowSyntheticNumbers` are not
     * permissions on the request; they are part of what the caller counts as
     * an acceptable value in the first place — "does this date exist" and
     * "would I accept a value built under the registry's own test-data
     * convention" — so relaxing either would answer a different question
     * than the one actually asked. A caller on default options still gets no
     * hint for a synthetic value, unchanged: allowSyntheticNumbers defaults
     * false, so the permissive attempt below refuses it exactly as the
     * caller's own request would. A caller who opted in gets the hint once a
     * genuinely valid synthetic value exists to report — which is also what
     * makes that path reachable by a test at all, since every safely
     * constructible Norwegian value is synthetic.
     *
     * @param list<Country> $excluding
     */
    public static function recognize(
        string $normalized,
        array $excluding = [],
        ?DateTimeImmutable $referenceDate = null,
        bool $allowSyntheticNumbers = false,
    ): ?Country {
        $permissiveOptions = new ParseOptions($referenceDate, allowSyntheticNumbers: $allowSyntheticNumbers);

        foreach (self::countriesWithASchemeToAsk($excluding) as $country) {
            if (self::acceptsUnderPermissiveOptions($country, $normalized, $permissiveOptions)) {
                return $country;
            }

            // No shape fallback for a country whose scheme just refused: a
            // shape match that the country's own scheme rejects is exactly
            // the false positive this tier exists to avoid.
        }

        foreach (self::shapesWithNoSchemeBehindThem($excluding) as $countryCode => $pattern) {
            // Anchored here rather than in the spec, so a fragment cannot
            // become a substring match in one runtime and a whole-string
            // match in the other. The whole pattern is wrapped in its own
            // group before anchoring: a country with more than one scheme
            // (Sweden) is stored as an alternation, and anchoring a bare
            // '/^A|B$/' would anchor only the first branch — the twin file
            // wraps for the same reason.
            if (preg_match('/^(?:' . $pattern . ')$/', $normalized) === 1) {
                return Country::from($countryCode);
            }
        }

        return null;
    }

    /**
     * @param list<Country> $excluding
     * @return list<Country>
     */
    private static function countriesWithASchemeToAsk(array $excluding): array
    {
        $countries = [];

        foreach (array_keys(SpecData::COUNTRY_SHAPES) as $countryCode) {
            $country = Country::from($countryCode);

            if (! in_array($country, $excluding, true) && SupportedSchemes::hasScheme($country)) {
                $countries[] = $country;
            }
        }

        return $countries;
    }

    /**
     * @param list<Country> $excluding
     * @return array<string, string>
     */
    private static function shapesWithNoSchemeBehindThem(array $excluding): array
    {
        $shapes = [];

        foreach (SpecData::COUNTRY_SHAPES as $countryCode => $pattern) {
            $country = Country::from($countryCode);

            if (! in_array($country, $excluding, true) && ! SupportedSchemes::hasScheme($country)) {
                $shapes[$countryCode] = $pattern;
            }
        }

        return $shapes;
    }

    /**
     * True rather than a SchemeResult|null, because every caller only ever
     * asks whether one of the country's schemes accepted the value — never
     * which one, or with what result. `instanceof`, not `is_object()`:
     * ParseFailure is a PHP enum, and enums are objects too, so `is_object()`
     * cannot tell a failure from a real SchemeResult. See CLAUDE.md.
     */
    private static function acceptsUnderPermissiveOptions(
        Country $country,
        string $normalized,
        ParseOptions $permissiveOptions,
    ): bool {
        return array_any(
            SupportedSchemes::resultsFor($country, $normalized, $permissiveOptions),
            static fn(SchemeResult|ParseFailure $result): bool => $result instanceof SchemeResult,
        );
    }
}
