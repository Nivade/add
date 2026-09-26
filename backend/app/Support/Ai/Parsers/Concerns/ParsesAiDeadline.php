<?php

declare(strict_types=1);

namespace App\Support\Ai\Parsers\Concerns;

use App\Support\Ai\Exceptions\AiResponseInvalid;
use Carbon\CarbonImmutable;
use Throwable;

trait ParsesAiDeadline
{
    /** An answer carrying its own offset keeps it; a naive one means the clock the person reads. */
    private function deadline(mixed $value, string $timezone, string $operation): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '' || strtolower(trim($value)) === 'null') {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value), $timezone);
        } catch (Throwable $exception) {
            throw new AiResponseInvalid("{$operation} returned an unreadable deadline_at: ".trim($value), previous: $exception);
        }
    }
}
