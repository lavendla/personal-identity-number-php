<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Enums;

/**
 * What the registries encode, which is binary. Not a statement about anything
 * else — consumers needing a richer model should carry their own alongside it.
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
}
