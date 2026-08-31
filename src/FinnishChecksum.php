<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;

final class FinnishChecksum
{
    /**
     * The control character is the thirty-one-character alphabet indexed by the
     * nine digits surrounding the intermediate character, taken as one number,
     * modulo 31.
     *
     * The intermediate character is skipped rather than included, and that is
     * the whole reason the two digit runs are concatenated: `ddmmyy` and the
     * individual number are checksummed as a single nine-digit number, so the
     * same nine digits carry the same control character under every marker the
     * decree assigns. A marker that changed the control character would make
     * the 2023 reform a breaking change to every code already issued.
     *
     * Unlike Norway's modulus-11, there is no unissuable remainder: all
     * thirty-one values index a real character, so every nine-digit run has
     * exactly one valid control character.
     *
     * Takes the whole eleven-character code rather than its parts, so the
     * caller cannot slice it one way here and another way in the twin runtime.
     */
    public static function matches(string $code): bool
    {
        $digits = substr($code, 0, 6) . substr($code, 7, 3);

        return SpecData::FI_CONTROL_ALPHABET[(int) $digits % 31] === substr($code, 10, 1);
    }
}
