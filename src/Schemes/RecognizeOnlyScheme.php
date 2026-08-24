<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Schemes;

use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Generated\SpecData;

/**
 * One scheme for every country this package recognises without supporting,
 * because recognition is shape-matching and nothing else. Norway and Finland
 * differ only in the shape they match, and a shape lives in spec/, so there is
 * nothing left to give each country its own class for.
 *
 * Shape only, deliberately. Neither country's checksum is implemented: a scheme
 * that recognises and refuses has no use for the difference between a valid
 * foreign number and an invalid one, and implementing a checksum is the first
 * step toward accidentally supporting the country. A caller learns what the
 * value looks like, never anything about a bearer.
 */
final class RecognizeOnlyScheme
{
    /**
     * The country whose shape matched, or null.
     *
     * Deliberately not a SchemeResult. A recognize-only scheme never produces a
     * number, and giving it a real scheme's return type would invite a caller —
     * or a later maintainer wiring it into the registry — to treat a recognition
     * as a parse. Returning a Country makes that impossible to do by accident.
     */
    public static function recognize(string $normalized): ?Country
    {
        foreach (SpecData::RECOGNIZE_ONLY_SHAPES as $countryCode => $pattern) {
            // Anchored here rather than in the spec, so a fragment cannot become
            // a substring match in one runtime and a whole-string match in the
            // other. The twin file anchors the same way.
            if (preg_match('/^' . $pattern . '$/', $normalized) === 1) {
                return Country::from($countryCode);
            }
        }

        return null;
    }
}
