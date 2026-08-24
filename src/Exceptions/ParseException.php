<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber\Exceptions;

use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;
use RuntimeException;

final class ParseException extends RuntimeException
{
    public function __construct(private readonly ParseFailure $failure)
    {
        parent::__construct($failure->value);
    }

    public function getFailure(): ParseFailure
    {
        return $this->failure;
    }
}
