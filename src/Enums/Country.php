<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Enums;

/**
 * The national registry that issued a number — never where a person lives.
 *
 * These disagree for foreign nationals: a Norwegian resident in Sweden has a
 * Swedish address and a Norwegian identity number. Using a residence country
 * here produces wrong data silently.
 */
enum Country: string
{
    case Sweden = 'SE';
    case Denmark = 'DK';
    case Norway = 'NO';
    case Finland = 'FI';
}
