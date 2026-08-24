<?php

declare(strict_types=1);

namespace Lavendla\PersonalIdentityNumber;

use Lavendla\PersonalIdentityNumber\Enums\Country;
use Lavendla\PersonalIdentityNumber\Enums\ParseFailure;

final readonly class ParseOutcome
{
    /** @param list<PersonalIdentityNumber> $candidates */
    private function __construct(
        private array $candidates,
        private ?ParseFailure $failure,
        private ?Country $recognizedCountry,
    ) {}

    /** @param list<PersonalIdentityNumber> $candidates */
    public static function resolved(array $candidates): self
    {
        return new self($candidates, null, null);
    }

    public static function failed(ParseFailure $failure, ?Country $recognizedCountry = null): self
    {
        return new self([], $failure, $recognizedCountry);
    }

    public function succeeded(): bool
    {
        return $this->failure === null && $this->candidates !== [];
    }

    /** Null when ambiguous — resolving that needs information this package does not have. */
    public function number(): ?PersonalIdentityNumber
    {
        return count($this->candidates) === 1 ? $this->candidates[0] : null;
    }

    public function failure(): ?ParseFailure
    {
        return $this->failure;
    }

    public function recognizedCountry(): ?Country
    {
        return $this->recognizedCountry;
    }

    /** @return list<PersonalIdentityNumber> */
    public function candidates(): array
    {
        return $this->candidates;
    }

    public function isAmbiguous(): bool
    {
        return count($this->candidates) > 1;
    }
}
