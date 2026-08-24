<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Enums;

enum Format: string
{
    case Canonical = 'canonical';
    case Display = 'display';
    case Short = 'short';
    case Masked = 'masked';
}
