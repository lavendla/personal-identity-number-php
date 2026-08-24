<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Generated\SpecData;
use SensitiveParameter;

final class Normalizer
{
    /**
     * Folds known character variants and rejects anything outside the allow
     * list. Returns null when a character is not allowed — deleting unexpected
     * characters would turn one person's number into another's.
     */
    public static function normalize(#[SensitiveParameter] string $raw): ?string
    {
        $normalized = '';

        foreach (mb_str_split($raw, 1, 'UTF-8') as $character) {
            $folded = SpecData::FOLD[$character] ?? $character;

            if ($folded === '') {
                continue;
            }

            if (! in_array($folded, SpecData::ALLOWED, true)) {
                return null;
            }

            $normalized .= $folded;
        }

        return $normalized;
    }
}
