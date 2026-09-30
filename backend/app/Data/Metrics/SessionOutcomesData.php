<?php

declare(strict_types=1);

namespace App\Data\Metrics;

use Spatie\LaravelData\Data;

class SessionOutcomesData extends Data
{
    public function __construct(
        public int $ended,
        public int $withProgress,
        public int $distractions,
        public int $backAfterDistraction,
    ) {}
}
