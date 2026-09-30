<?php

declare(strict_types=1);

namespace App\Data\Metrics;

use Spatie\LaravelData\Data;

class RecoveryOutcomesData extends Data
{
    public function __construct(
        public int $landedUnfinished,
        public int $pickedBackUp,
        public int $skippedSteps,
        public int $skippedThenDone,
    ) {}
}
