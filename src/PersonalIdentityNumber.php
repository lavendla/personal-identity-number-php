<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use DateTimeImmutable;
use DateTimeZone;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\Format;
use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;
use Lavendla\PersonalIdentityNumber\Exceptions\ParseException;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use Lavendla\PersonalIdentityNumber\Schemes\CountryRecognizer;
use SensitiveParameter;

final readonly class PersonalIdentityNumber
{
    private function __construct(
        private Scheme $scheme,
        private string $canonical,
        private ?BirthTime $birth,
        private ?Gender $genderValue,
        private ?DateTimeImmutable $referenceDate,
        private bool $synthetic,
    ) {}

    public static function parse(
        #[SensitiveParameter]
        string $raw,
        Country $issuedBy,
        ParseOptions $options,
    ): self {
        $outcome = self::explain($raw, $issuedBy, $options);
        $number = $outcome->number();

        if ($number === null) {
            throw new ParseException($outcome->failure() ?? ParseFailure::NotAnIdentityNumber);
        }

        return $number;
    }

    public static function tryParse(
        #[SensitiveParameter]
        string $raw,
        Country $issuedBy,
        ParseOptions $options,
    ): ?self {
        return self::explain($raw, $issuedBy, $options)->number();
    }

    public static function validates(
        #[SensitiveParameter]
        string $raw,
        Country $issuedBy,
        ParseOptions $options,
    ): bool {
        return self::explain($raw, $issuedBy, $options)->succeeded();
    }

    public static function explain(
        #[SensitiveParameter]
        string $raw,
        ?Country $issuedBy,
        ParseOptions $options,
    ): ParseOutcome {
        $normalized = Normalizer::normalize($raw);

        if ($normalized === null) {
            return ParseOutcome::failed(ParseFailure::InvalidCharacter);
        }

        $countries = self::countriesToTry($issuedBy);

        if ($countries === []) {
            return ParseOutcome::failed(ParseFailure::CountryNotSupported);
        }

        return self::outcomeFrom($countries, $normalized, $options);
    }

    /**
     * The registry. A country joins by appearing here and in
     * SupportedSchemes::resultsFor().
     *
     * A named country narrows this to that country alone; a null country runs
     * every scheme, which is what lets detect() report a value valid under more
     * than one registry rather than picking a winner.
     *
     * @return list<Country>
     */
    private static function countriesToTry(?Country $issuedBy): array
    {
        if ($issuedBy === null) {
            return SupportedSchemes::SUPPORTED_COUNTRIES;
        }

        return in_array($issuedBy, SupportedSchemes::SUPPORTED_COUNTRIES, true) ? [$issuedBy] : [];
    }

    /**
     * How specific each refusal is, lowest first. Needed because more than one
     * scheme can now refuse the same value with different reasons, and leaving
     * the answer to registry order means adding a scheme silently changes
     * existing failure codes.
     *
     * The ordering that matters: ChecksumMismatch beats ImpossibleDate, because
     * an organization-shaped value with a bad check digit makes the organization
     * scheme say ChecksumMismatch while the personal scheme says ImpossibleDate
     * — its month would be 20 or more. The third digit already established which
     * kind of number it is, so the checksum is the useful answer.
     *
     * Held as data, and FailurePrecedenceTest asserts every ParseFailure member
     * appears here. Without that test the ?? fallback below would silently rank
     * a new member last, which is precisely what it did when
     * ImplausibleBirthDate was added.
     */
    private const array FAILURE_PRECEDENCE = [
        // Cannot reach the comparison today: explain() refuses malformed input
        // before any scheme runs. Ranked anyway, because the completeness test
        // demands every member be placed rather than silently defaulted, and
        // "these characters are not allowed" is the most definite refusal there is.
        'invalid-character'       => 0,
        'scheme-not-allowed'      => 1,
        'checksum-mismatch'       => 2,
        'impossible-date'         => 3,
        'future-birth-date'       => 3,
        'implausible-birth-date'  => 3,
        // Norway's checksum is computed after the +80 offset is added, so a
        // synthetic number passes modulus-11 like any other -- this is a refusal
        // about the decoded month, ranked with the other checksum-passing,
        // date-derived refusals rather than above or below them.
        'synthetic-number'        => 3,
        'century-required'        => 4,
        'reference-date-required' => 4,
        'country-not-supported'   => 4,
        'unsupported-scheme'      => 5,
        'not-an-identity-number'  => 6,
    ];

    /** @param non-empty-list<ParseFailure> $failures */
    private static function mostSpecificFailure(array $failures): ParseFailure
    {
        $best = $failures[0];

        foreach ($failures as $failure) {
            if (self::specificityOf($failure) < self::specificityOf($best)) {
                $best = $failure;
            }
        }

        return $best;
    }

    private static function specificityOf(ParseFailure $failure): int
    {
        // No fallback. PHPStan proves the table is total, so an unranked member
        // is a static error rather than a silent default — which is what the
        // comment on FAILURE_PRECEDENCE always claimed and did not deliver.
        return self::FAILURE_PRECEDENCE[$failure->value];
    }

    /**
     * @param list<Country> $countries
     */
    private static function outcomeFrom(array $countries, string $normalized, ParseOptions $options): ParseOutcome
    {
        $candidates = [];
        $failures = [];

        foreach ($countries as $country) {
            foreach (SupportedSchemes::resultsFor($country, $normalized, $options) as $parsed) {
                if ($parsed instanceof ParseFailure) {
                    $failures[] = $parsed;

                    continue;
                }

                $candidates[] = new self(
                    $parsed->scheme,
                    $parsed->canonical,
                    $parsed->birthTime,
                    $parsed->gender,
                    $options->referenceDate,
                    $parsed->synthetic,
                );
            }
        }

        if ($candidates !== []) {
            return ParseOutcome::resolved($candidates);
        }

        // Consulted only once every real scheme has refused, because a
        // recognition is not a candidate: CountryRecognizer produces no
        // number, so detect() cannot report one and succeeded() stays false.
        //
        // That ordering is also the answer to the Denmark/Finland collision. A
        // Finnish code with a '-' and a digit control character is
        // character-for-character a Danish CPR number, and most such codes parse
        // as valid Danish ones — so a parse outranking a recognition is what
        // keeps `131052-3085` Danish rather than reclassifying it as foreign.
        //
        // Excludes every country already consulted, not only a single named
        // one: with no country named, $countries is every supported country,
        // and without this a value that fails both Swedish schemes would be
        // reported as looking Swedish — the exact "it refused it and it looks
        // like itself" noise this exists to prevent.
        //
        // The caller's referenceDate and allowSyntheticNumbers travel
        // through unchanged, because the question a hint answers is "does
        // this date exist" and "would this caller accept a value built under
        // the registry's own test-data convention", not "does it exist
        // relative to options nobody asked about". The other allow* flags do
        // not travel: CountryRecognizer::recognize() asks its own permissive
        // question for a supported country. See CountryRecognizer.
        $recognized = CountryRecognizer::recognize(
            $normalized,
            $countries,
            $options->referenceDate,
            $options->allowSyntheticNumbers,
        );

        if ($recognized !== null) {
            $failures[] = ParseFailure::UnsupportedScheme;
        }

        // Every scheme refused, and the answer depends on how many countries were
        // consulted — two separate decisions that must not be conflated.
        //
        // One country named: report its most specific reason. Sweden has two
        // schemes now, and the precedence table exists so that adding one cannot
        // change the failure code an existing fixture pins. A recognition takes
        // part in that comparison rather than short-circuiting it: it outranks
        // "no idea what this is" and loses to a named registry's specific
        // refusal, which is what ranking `unsupported-scheme` between them means.
        //
        // No country named: report the generic reason, however specific one
        // scheme was. With several registries consulted, "Sweden says the check
        // digit is wrong" is not an answer to "what is this?" — that is Plan 2's
        // decision for detect() and this does not reopen it. But "this is a
        // Norwegian number" *is* an answer to that question, so a recognition is
        // reported here too. Deciding otherwise would withhold the only useful
        // thing the package knows from the one caller who asked exactly that.
        if ($failures === [] || count($countries) > 1) {
            return $recognized === null
                ? ParseOutcome::failed(ParseFailure::NotAnIdentityNumber)
                : ParseOutcome::failed(ParseFailure::UnsupportedScheme, $recognized);
        }

        $failure = self::mostSpecificFailure($failures);

        // Attached regardless of which failure won, with one exception below.
        // A named country's own scheme can refuse for a reason more specific
        // than UnsupportedScheme — Sweden saying ChecksumMismatch to a value
        // Denmark's own scheme genuinely parses — and the hint is exactly as
        // informative attached to that refusal as it would be attached to
        // UnsupportedScheme. Withholding it there would report less than the
        // package actually knows.
        //
        // Suppressed for a request-level failure. CenturyRequired and
        // SchemeNotAllowed mean the asked country's own scheme would have
        // parsed the value given different options (a reference date, an
        // allow* flag), so recognizedCountry() would tell the caller they
        // may have named the wrong country when the actual problem is the
        // request — pointing them at the wrong fix while they are holding a
        // perfectly good number. See spec/error-codes.json's
        // requestLevelFailuresNote.
        return ParseOutcome::failed(
            $failure,
            self::isRequestLevelFailure($failure) ? null : $recognized,
        );
    }

    private static function isRequestLevelFailure(ParseFailure $failure): bool
    {
        return in_array($failure->value, SpecData::REQUEST_LEVEL_FAILURES, true);
    }

    /**
     * Every interpretation of the input, in no priority order.
     *
     * For search paths, where the country is what the caller is trying to find
     * out. Deliberately never picks a winner: a "best match" would recreate
     * inside the package the collision bug it exists to prevent.
     *
     * @return list<self>
     */
    public static function detect(#[SensitiveParameter] string $raw, ParseOptions $options): array
    {
        return self::explain($raw, null, $options)->candidates();
    }

    /** Asserts the result is a person, so a company cannot enter a person-matching path. */
    public static function parseForPerson(
        #[SensitiveParameter]
        string $raw,
        Country $issuedBy,
        DateTimeImmutable $referenceDate,
    ): self {
        $number = self::parse($raw, $issuedBy, new ParseOptions($referenceDate, allowOrganizationNumber: false));

        if (! $number->isPerson()) {
            throw new ParseException(ParseFailure::SchemeNotAllowed);
        }

        return $number;
    }

    /** Asserts the result is an organization number, not merely a valid number. */
    public static function parseForOrganization(
        #[SensitiveParameter]
        string $raw,
        Country $issuedBy,
        DateTimeImmutable $referenceDate,
    ): self {
        $number = self::parse($raw, $issuedBy, new ParseOptions($referenceDate));

        if ($number->scheme() !== Scheme::SeOrganizationNumber) {
            throw new ParseException(ParseFailure::SchemeNotAllowed);
        }

        return $number;
    }

    public function scheme(): Scheme
    {
        return $this->scheme;
    }

    public function country(): Country
    {
        return $this->scheme->country();
    }

    public function canonical(): string
    {
        return $this->canonical;
    }

    public function isPerson(): bool
    {
        return $this->scheme->isPerson();
    }

    public function birthDate(): ?DateTimeImmutable
    {
        // Null for a partial birth time as well as for no birth time at all. The
        // two absences differ inside the package and not to a caller: either way
        // there is no date to report, which is docs/open-threads.md §1.4's
        // decision — no second success tier, no caveat flag.
        return $this->birth?->toBirthDate()?->toDateTime();
    }

    public function gender(): ?Gender
    {
        return $this->genderValue;
    }

    /**
     * True only for a value accepted under Skatteetaten/Digdir's `+80`
     * test-data convention with `allowSyntheticNumbers` set. See the comment
     * on SchemeResult::$synthetic for why every other scheme reports false
     * explicitly rather than by default.
     */
    public function isSynthetic(): bool
    {
        return $this->synthetic;
    }

    /**
     * Compares calendar dates, never instants. `DateTimeImmutable::diff()`
     * measures the exact interval between two timestamps, which would drift
     * by a day whenever the reference date and the (always-UTC) birth date
     * carry different times of day. Reducing both sides to `Y-m-d` avoids
     * that, but only once the reference date is normalized to UTC first:
     * TypeScript's `Date` has no timezone concept and always reads the UTC
     * calendar day of an instant, so a reference date read in whatever
     * timezone the caller attached to it could resolve some instants to a
     * different calendar day — and therefore a different age — than the
     * paired TypeScript runtime resolves for that same instant. Normalizing
     * here keeps both runtimes agreeing for any given instant, not just for
     * dates already expressed in UTC.
     */
    public function ageOn(DateTimeImmutable $referenceDate): ?int
    {
        $referenceDate = $referenceDate->setTimezone(new DateTimeZone('UTC'));

        if (! $birthDate = $this->birthDate()) {
            return null;
        }

        $birthIso = $birthDate->format('Y-m-d');
        $referenceIso = $referenceDate->format('Y-m-d');

        if ($birthIso > $referenceIso) {
            return null;
        }

        $age = (int) $referenceDate->format('Y') - (int) $birthDate->format('Y');
        $hasHadBirthdayThisYear = substr($referenceIso, 5) >= substr($birthIso, 5);

        return $hasHadBirthdayThisYear ? $age : $age - 1;
    }

    public function equals(self $other): bool
    {
        return $this->country() === $other->country() && $this->canonical === $other->canonical;
    }

    public function format(Format $format, ?DateTimeImmutable $referenceDate = null): string
    {
        return match ($format) {
            Format::Canonical => $this->canonical,
            Format::Display   => $this->displayForm(),
            Format::Short     => $this->shortForm($referenceDate ?? $this->referenceDate),
            Format::Masked    => substr($this->canonical, 0, -4) . '****',
        };
    }

    private function displayForm(): string
    {
        $shown = substr($this->canonical, $this->scheme->displayElision());
        $split = $this->scheme->displaySplit();

        // Finland. Its canonical form already carries its intermediate
        // character at the position a separator would go, and that character is
        // part of the identity under decree 690/2022 rather than punctuation --
        // so the written form is the canonical form and there is nothing to
        // insert.
        if ($split === null) {
            return $shown;
        }

        return substr($shown, 0, $split) . '-' . substr($shown, $split);
    }

    private function shortForm(?DateTimeImmutable $referenceDate): string
    {
        // No century to elide means no age to report, so the short form is the
        // display form and needs no reference date. True of Denmark, whose
        // ten-digit form carries no century, and of organization numbers, which
        // have no bearer to have an age.
        if (! $this->scheme->shortFormElidesCentury()) {
            return $this->displayForm();
        }

        if ($referenceDate === null) {
            throw new ParseException(ParseFailure::ReferenceDateRequired);
        }

        $separator = $this->hasTurnedOneHundredOn($referenceDate) ? '+' : '-';

        return substr($this->canonical, 2, 6) . $separator . substr($this->canonical, 8);
    }

    /**
     * Whether the bearer has turned 100 at the reference date, which is the only
     * thing Format::Short's separator reports.
     *
     * A partial birth date has no age, and `?? 0` would have rendered '-' for
     * every one of them. That is not a cosmetic loss: the '+' is what recovers the
     * century when the short form is read back, so `191500722390` would render
     * `150072-2390` and re-parse as 2015. The year is known even when the month
     * and day are not, so it answers the question on its own — a bearer born in
     * 1915 is past 100 at any 2026 reference date, whatever month they were born
     * in.
     *
     * Complete dates keep the exact-age rule. Only the partial case falls back to
     * the year, because only there is the exact age genuinely unavailable.
     */
    private function hasTurnedOneHundredOn(DateTimeImmutable $referenceDate): bool
    {
        if (($age = $this->ageOn($referenceDate)) !== null) {
            return $age >= 100;
        }

        return $this->birth !== null
            && (int) $referenceDate->format('Y') - $this->birth->year >= 100;
    }

    /** @return array<string, string> */
    public function __debugInfo(): array
    {
        return ['scheme' => $this->scheme->value, 'value' => $this->format(Format::Masked)];
    }
}
