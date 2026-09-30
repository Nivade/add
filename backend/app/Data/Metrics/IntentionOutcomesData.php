<?php

declare(strict_types=1);

namespace App\Data\Metrics;

use Spatie\LaravelData\Data;

class IntentionOutcomesData extends Data
{
    public function __construct(
        public int $created,
        public int $done,
        public int $setAside,
        public int $open,
        public ?int $medianMinutesToStart,
        public ?int $p75MinutesToStart,
        public int $notStarted,
    ) {}
}
