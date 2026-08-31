<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Schemes\DanishCprNumberScheme;
use Lavendla\PersonalIdentityNumber\Schemes\FinnishPersonalIdentityCodeScheme;
use Lavendla\PersonalIdentityNumber\Schemes\NorwegianDNumberScheme;
use Lavendla\PersonalIdentityNumber\Schemes\NorwegianNationalIdentityNumberScheme;
use Lavendla\PersonalIdentityNumber\Schemes\SwedishOrganizationNumberScheme;
use Lavendla\PersonalIdentityNumber\Schemes\SwedishPersonalNumberScheme;

/**
 * Countries whose schemes actually run when a value is parsed. Every country
 * the Country enum has is now on this list. Both PersonalIdentityNumber's
 * dispatcher and CountryRecognizer need exactly this list -- the dispatcher to
 * know which countries to run schemes for at all, the recognizer to know which
 * countries it can actually attempt a parse for rather than merely shape-test
 * -- so it lives here once rather than twice.
 *
 * It is deliberately still a list rather than being replaced by
 * `Country::cases()`, now that the two happen to coincide: the distinction
 * between having a Country member and having a scheme that runs is what the
 * recognize-only tier is built on, and README.md §12 step 1 requires the next
 * country to ship through that tier first. Collapsing the two because they are
 * momentarily equal is how that step gets skipped.
 *
 * There is no separate support gate anywhere in the runtime: a SchemeResult
 * a scheme returns here becomes a parse candidate directly, so joining this
 * list *is* the promotion to real parsing in everything but the `support`
 * field each file under spec/schemes/ carries for schemes:check's own
 * bookkeeping. Norway and Finland both joined here while all three of
 * spec/schemes/no/national-identity-number.json's,
 * spec/schemes/no/d-number.json's and
 * spec/schemes/fi/personal-identity-code.json's `support` fields still read
 * `"recognize-only"` -- see those files' `supportNote`s for what that gap
 * means and why it exists, rather than inferring from this list that the
 * field is accurate.
 */
final class SupportedSchemes
{
    /** @var list<Country> */
    public const array SUPPORTED_COUNTRIES = [
        Country::Sweden,
        Country::Denmark,
        Country::Norway,
        Country::Finland,
    ];

    /**
     * Whether this country has schemes that actually run, as opposed to only a
     * shape in spec/schemes/. Asked rather than tested inline against
     * SUPPORTED_COUNTRIES so CountryRecognizer's two tiers have one question to
     * ask instead of two spellings of it -- and so the answer stays a runtime
     * question. Every country the enum has answers true today; the moment the
     * next one is added, with a Country member and a recognitionPattern but no
     * scheme yet, it answers false and the recognizer's shape tier has
     * something to do again. That is README.md section 12 step 1.
     */
    public static function hasScheme(Country $country): bool
    {
        return in_array($country, self::SUPPORTED_COUNTRIES, true);
    }

    /**
     * Every scheme a country has, each returning its own result. Sweden has
     * two, so a Swedish value is offered to both — and the organization
     * scheme has to run even when the caller excluded organization numbers,
     * because reporting SchemeNotAllowed requires recognising the number
     * first.
     *
     * Shared between the dispatcher and the recognizer, which call it with
     * different options for different reasons: the dispatcher passes the
     * caller's own options, because SchemeNotAllowed is a real answer to a
     * real question; the recognizer passes permissive ones, because a hint
     * answers "would this country's registry accept this number", not
     * "would it accept it under the options you happened to pass this
     * call".
     *
     * @return non-empty-list<SchemeResult|ParseFailure>
     */
    public static function resultsFor(
        Country $country,
        string $normalized,
        ParseOptions $options,
    ): array {
        return match ($country) {
            Country::Sweden => [
                SwedishPersonalNumberScheme::parse(
                    $normalized,
                    $options->referenceDate,
                    $options->allowCoordinationNumber,
                    $options->allowUnknownBirthNumber,
                ),
                SwedishOrganizationNumberScheme::parse($normalized, $options->allowOrganizationNumber),
            ],
            Country::Denmark => [DanishCprNumberScheme::parse($normalized, $options->referenceDate)],
            Country::Norway  => [
                NorwegianNationalIdentityNumberScheme::parse(
                    $normalized,
                    $options->referenceDate,
                    $options->allowSyntheticNumbers,
                ),
                NorwegianDNumberScheme::parse(
                    $normalized,
                    $options->referenceDate,
                    $options->allowSyntheticNumbers,
                ),
            ],
            Country::Finland => [
                FinnishPersonalIdentityCodeScheme::parse($normalized, $options->referenceDate),
            ],
        };
    }
}
