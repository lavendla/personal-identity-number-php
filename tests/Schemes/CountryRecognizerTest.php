<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Schemes;

use DateTimeImmutable;
use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use Lavendla\PersonalIdentityNumber\Schemes\CountryRecognizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every constructed foreign number here fails its own country's checksum on
 * purpose — see spec/fixtures/foreign/PROVENANCE.md. The Swedish and Danish
 * numbers are either Skatteverket's own published test number, the ambiguous
 * Swedish/Danish collision already used in spec/fixtures/ambiguous/se-dk.json,
 * or the constructed, bearer-less serial-zero case already used in
 * spec/fixtures/dk/cpr-number.json — see spec/fixtures/dk/PROVENANCE.md. The
 * valid Finnish code is DVV's own published test identity, safe under
 * CLAUDE.md's Exception 3.
 *
 * **Every country is on the real-parse tier now.** Recognition asks whether
 * that country's own registry would accept the value, permissively, and
 * reports nothing when it would not — so no shape match anywhere in this file
 * survives its own country's refusal. Norway moved to that tier when its
 * fødselsnummer scheme was wired up and Finland when its own was; the
 * shape-only tier remains in the code, with no country left to use it, because
 * README.md §12 step 1 requires the next country to ship through it first.
 */
final class CountryRecognizerTest extends TestCase
{
    private const string REFERENCE_DATE = '2026-08-16T00:00:00Z';

    /**
     * Once Norway joined SupportedSchemes::SUPPORTED_COUNTRIES, this same
     * deliberately checksum-invalid number stopped being recognized: the
     * tier asks whether Norway's own registry would accept the value, real
     * parse and all, and a bad check digit means it would not. Before this it
     * was recognized purely by shape, which is exactly the false positive
     * the two-tier recognizer exists to avoid — see
     * spec/fixtures/foreign/no-fi.json's rewritten provenance notes for the
     * fixtures pinning the same behaviour end to end.
     *
     * Also confirms it is not recognized as Finnish instead: Finland's shape
     * requires a non-digit control character at the seventh position and
     * this input has none, so null already implies "not Finland" — folded in
     * here rather than kept as its own test, since no mutation could redden
     * the standalone assertion without also reddening this one.
     */
    #[Test]
    public function noLongerRecognizesAnInvalidElevenDigitNumberAsNorwegianNowThatNorwayIsSupported(): void
    {
        $this->assertNull(CountryRecognizer::recognize('13108633528'));
        $this->assertNotSame(Country::Finland, CountryRecognizer::recognize('13108633528'));
    }

    /**
     * Finland is on the real-parse tier now too, so an intermediate character
     * is no longer enough on its own: this is DVV's own published test identity
     * 010280-952L, which Finland's scheme genuinely accepts (individual number
     * 952, inside the 900-999 block DVV states it does not issue from — see
     * CLAUDE.md's Exception 3). Its checksum-invalid sibling 131052-308U is the
     * next test, and the pair is what makes the tier's behaviour visible.
     */
    #[Test]
    public function recognizesAGenuinelyValidFinnishCodeAsFinnish(): void
    {
        $this->assertSame(Country::Finland, CountryRecognizer::recognize('010280-952L'));
    }

    /** The control character for 131052308 is T, not U. */
    #[Test]
    public function noLongerRecognizesAChecksumInvalidFinnishCodeNowThatFinlandIsSupported(): void
    {
        $this->assertNull(CountryRecognizer::recognize('131052-308U'));
    }

    /**
     * The path allowSyntheticNumbers now unlocks: from
     * spec/sources/norway-synthetic/generated.csv, checksum-valid,
     * individual 101, resolving to 1975-04-05. Every safely constructible
     * Norwegian value is synthetic-shaped, so before allowSyntheticNumbers
     * travelled through to the permissive attempt, no fixture or test could
     * ever show Norway being genuinely accepted here — this is that proof.
     */
    #[Test]
    public function recognizesAGenuinelyValidSyntheticNorwegianValueWhenTheCallerOptsIn(): void
    {
        $this->assertSame(Country::Norway, CountryRecognizer::recognize('05847510147', [], null, true));
    }

    /**
     * Without opting in, the same value is refused by the permissive attempt
     * exactly as it would be by a real caller on default options —
     * confirming allowSyntheticNumbers stays restrictive by default even
     * though it now travels through, per the class docblock above.
     */
    #[Test]
    public function doesNotRecognizeTheSameSyntheticNorwegianValueWithoutOptingIn(): void
    {
        $this->assertNull(CountryRecognizer::recognize('05847510147'));
    }

    /**
     * A supported country is asked for real, not by shape: this is
     * Skatteverket's own published, Luhn-valid personnummer, so Sweden's own
     * scheme accepts it under permissive options and it is reported.
     */
    #[Test]
    public function recognizesAGenuinelyValidSwedishPersonnummerAsSwedish(): void
    {
        $this->assertSame(Country::Sweden, CountryRecognizer::recognize('190312049802'));
    }

    #[Test]
    public function recognizesNothingOnceSwedenIsExcludedForAValueOnlySwedensShapeEverMatched(): void
    {
        $this->assertNull(CountryRecognizer::recognize('190312049802', [Country::Sweden]));
    }

    /**
     * The central case this tier exists for: a value that is shape-valid for
     * both Sweden and Denmark (six digits, a hyphen, four digits) but which
     * neither country's own scheme would accept — day 48 exists in no month,
     * and the same digits are Denmark's rejected zero-serial fixture.
     * Recognition reports nothing rather than falling back to the shape that
     * would have matched, which is the false positive a shape-only test for
     * a supported country produced before this tier existed: Sweden's shape
     * alone matches essentially any ten-digit string (measured, 100%), so
     * shape-testing a supported country is not advisory, it is noise.
     */
    #[Test]
    public function doesNotRecognizeAShapeValidButUnparseableValueAsSwedishEvenWithDenmarkExcluded(): void
    {
        $this->assertNull(CountryRecognizer::recognize('2512480000', [Country::Denmark]));
    }

    #[Test]
    public function doesNotRecognizeAShapeValidButUnparseableValueAsAnything(): void
    {
        $this->assertNull(CountryRecognizer::recognize('2512480000'));
    }

    /**
     * The same non-number with its separator restored is still shape-valid for
     * Finland — six digits, a hyphen as its intermediate character, three
     * digits, a digit control character — and while Finland was recognize-only
     * it was reported as Finnish for exactly that reason. It no longer is:
     * Finland's scheme is real, and the modulus-31 alphabet indexes 6 for
     * 251248000, not 0. With every country now on the real-parse tier, this
     * file no longer contains a single case where a shape match survives its
     * own country's refusal. See spec/fixtures/dk/cpr-number.json's
     * dk-cpr-number-rejects-a-serial-of-zero fixture, which changed with it.
     */
    #[Test]
    public function noLongerRecognizesTheSameShapeValidValueAsFinnishEither(): void
    {
        $this->assertNull(CountryRecognizer::recognize('251248-0000', [Country::Denmark]));
    }

    /**
     * referenceDate travels through unchanged to the permissive attempt.
     * Without one, Sweden's own scheme refuses this century-incomplete short
     * form with CenturyRequired — not a SchemeResult, so no accept — and
     * Denmark, needing no reference date to resolve its century table, is
     * asked next and accepts it instead. With the caller's referenceDate
     * supplied, Sweden can resolve the century, finds Skatteverket's own
     * published 1903 date, and accepts it — Sweden is earlier in
     * SpecData::COUNTRY_SHAPES, so it wins. Same input, different answer,
     * entirely because of whether a reference date reached the attempt.
     */
    #[Test]
    public function withoutAReferenceDateACenturyIncompleteSwedishShortFormIsAskedOfDenmarkInstead(): void
    {
        $this->assertSame(Country::Denmark, CountryRecognizer::recognize('031204-9802'));
    }

    #[Test]
    public function withTheCallersReferenceDateSwedenResolvesTheCenturyAndIsRecognized(): void
    {
        $this->assertSame(
            Country::Sweden,
            CountryRecognizer::recognize('031204-9802', [], new DateTimeImmutable(self::REFERENCE_DATE)),
        );
    }

    /**
     * The published Skatteverket/CPR collision: valid as a Swedish short
     * form once a reference date resolves its century, and independently
     * valid as a Danish reading of the same ten digits (Denmark has no
     * checksum to catch a misread). $excluding removes each in turn so both
     * real acceptances are exercised, then removes both together to confirm
     * neither shape survives on its own once every supported country has
     * genuinely refused or been excluded — Norway's shape needs eleven
     * digits and Finland's needs a non-digit at the seventh character, and
     * this input has neither.
     */
    #[Test]
    public function excludesEverySupportedCountryPassedNotOnlyTheFirstForAGenuineTwoCountryCollision(): void
    {
        $referenceDate = new DateTimeImmutable(self::REFERENCE_DATE);

        $this->assertSame(
            Country::Denmark,
            CountryRecognizer::recognize('2601012384', [Country::Sweden], $referenceDate),
        );
        $this->assertNull(
            CountryRecognizer::recognize('2601012384', [Country::Sweden, Country::Denmark], $referenceDate),
        );
    }

    #[Test]
    public function recognizesNothingWhenTheLengthMatchesNoShapeAtAll(): void
    {
        $this->assertNull(CountryRecognizer::recognize('131086335'));
    }

    /**
     * Empty as of spec 1.0.0: every scheme is `support: "full"`, and codegen
     * derives this constant from that field rather than from a hand-written
     * list, so it emptied itself when the three schemes were promoted.
     *
     * The assertion is kept, inverted, rather than deleted along with the
     * entries. Nothing in CountryRecognizer reads
     * SpecData::RECOGNIZE_ONLY_SHAPES -- it is COUNTRY_SHAPES the class
     * consults -- so without this test the constant has no reader at all, and
     * the comment explaining why it survives as the on-ramp README.md section
     * 12 step 1 requires is the only thing stopping someone deleting it. An
     * empty array is exactly the state the next country changes: it arrives
     * here first, alone, before it gets a scheme.
     */
    #[Test]
    public function recognizeOnlyShapesIsEmptyBecauseEverySchemeIsNowFull(): void
    {
        $this->assertSame([], SpecData::RECOGNIZE_ONLY_SHAPES);
    }
}
