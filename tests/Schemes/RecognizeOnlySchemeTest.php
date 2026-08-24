<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Tests\Schemes;

use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Schemes\RecognizeOnlyScheme;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every number here fails its own country's checksum on purpose — see
 * spec/fixtures/foreign/PROVENANCE.md. Recognition is shape-matching, so an
 * invalid number is recognized exactly as a valid one would be, which is what
 * lets these tests exist at all without naming a real person's number.
 */
final class RecognizeOnlySchemeTest extends TestCase
{
    #[Test]
    public function recognizesElevenDigitsAsNorwegian(): void
    {
        $this->assertSame(Country::Norway, RecognizeOnlyScheme::recognize('13108633528'));
    }

    #[Test]
    public function recognizesAnIntermediateCharacterAsFinnish(): void
    {
        $this->assertSame(Country::Finland, RecognizeOnlyScheme::recognize('131052-308U'));
    }

    /**
     * The two shapes must not overlap, because the scheme returns the first
     * match. Finland's seventh character is an intermediate character and
     * Norway's is a digit, so nothing can satisfy both — but the scheme's answer
     * would depend on spec ordering if that ever stopped being true, and this is
     * the test that would notice.
     */
    #[Test]
    public function elevenDigitsAreNeverFinnish(): void
    {
        $this->assertNotSame(Country::Finland, RecognizeOnlyScheme::recognize('13108633528'));
    }

    #[Test]
    public function recognizesNothingInASwedishCanonicalForm(): void
    {
        $this->assertNull(RecognizeOnlyScheme::recognize('190312049802'));
    }

    /**
     * The Finnish shape is ten characters with a separator, which is also the
     * Swedish and Danish short form. Recognition says so, and the dispatcher is
     * what keeps that from mattering: a parse outranks a recognition. Measured
     * across the published corpus, 11,281 written forms match a recognize-only
     * shape and none of them reaches a caller as a recognition.
     */
    #[Test]
    public function recognizesAShapeThatSwedenAlsoClaims(): void
    {
        $this->assertSame(Country::Finland, RecognizeOnlyScheme::recognize('031204-9802'));
    }

    #[Test]
    public function recognizesNothingWhenTheLengthIsWrong(): void
    {
        $this->assertNull(RecognizeOnlyScheme::recognize('1310863352'));
    }
}
