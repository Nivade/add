<?php

declare(strict_types=1);

namespace App\Data\Ai;

use Spatie\LaravelData\Data;

class ParsedStepData extends Data
{
    public function __construct(
        public string $title,
        public int $estimatedSeconds,
    ) {}
}
