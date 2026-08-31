<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Enums\Gender;
use Lavendla\PersonalIdentityNumber\Enums\Scheme;

/**
 * Everything a scheme knows about a number it has accepted. Derived data is
 * computed here, by the scheme that understands the digit layout, rather than
 * re-derived downstream by slicing a canonical form whose shape differs between
 * countries -- ten characters for Denmark, twelve for Sweden.
 *
 * A scheme with no birth time or no gender to offer passes null. That is how
 * organization numbers arrive without adding a branch to any accessor.
 *
 * The birth time is a BirthTime rather than a BirthDate because a Swedish
 * coordination number can declare its month or day unknown. Those are two
 * different absences and the value object has to be able to tell them apart: an
 * organization number has no birth time at all, while a partial coordination
 * number has one whose year is known and whose date cannot be formed.
 *
 * `$synthetic` is true only for a value accepted under Skatteetaten/Digdir's
 * `+80` test-data convention with `allowSyntheticNumbers` set. Every other
 * scheme has no such convention and passes false explicitly, rather than
 * defaulting the parameter, so a future scheme that forgets it fails to
 * compile instead of silently reporting an ordinary number as a test one.
 */
final readonly class SchemeResult
{
    public function __construct(
        public Scheme $scheme,
        public string $canonical,
        public ?BirthTime $birthTime,
        public ?Gender $gender,
        public bool $synthetic,
    ) {}
}
