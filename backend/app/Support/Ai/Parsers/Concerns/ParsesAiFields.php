<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers\Concerns;

use App\Support\Ai\Exceptions\AiResponseInvalid;
use Carbon\CarbonImmutable;
use Throwable;

trait ParsesAiFields
{
    /** An answer carrying its own offset keeps it; a naive one means the clock the person reads. */
    private function deadline(mixed $value, string $timezone, string $operation): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '' || strtolower(trim($value)) === 'null') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value), $timezone);
        } catch (Throwable $throwable) {
            throw new AiResponseInvalid("{$operation} returned an unreadable deadline_at: ".trim($value), previous: $throwable);
        }
    }

    /** Absent, null and blank all mean "not given"; anything but a string is a broken answer. */
    private function optionalText(mixed $value, string $field, string $operation): ?string
    {
        if ($value !== null && ! is_string($value)) {
            throw new AiResponseInvalid("{$operation} returned a non-string {$field}.");
        }

        return $value === null || trim($value) === '' ? null : trim($value);
    }
}
