<?php

declare(strict_types=1);

namespace App\Concerns;

/** A date written on the person's clock is stored as the instant it is, not as its wall clock. */
trait StoresDatesInUtc
{
    public function fromDateTime(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : $this->asDateTime($value)->utc()->format($this->getDateFormat());
    }
}
