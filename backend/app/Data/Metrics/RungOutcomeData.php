<?php

declare(strict_types=1);

namespace App\Data\Metrics;

use Spatie\LaravelData\Data;

/** `rung` is null for the starts the resolver would not have offered. */
class RungOutcomeData extends Data
{
    public function __construct(
        public ?string $rung,
        public int $starts,
        public int $doneInSession,
        public int $skippedInSession,
    ) {}
}
