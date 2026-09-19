<?php

declare(strict_types=1);

namespace App\Support\Time;

use Carbon\CarbonImmutable;

/** $phrase is the text the extractor claimed, so the model is never asked about it again. */
final readonly class ExtractedDeadline
{
    public function __construct(
        public CarbonImmutable $at,
        public string $phrase,
    ) {}
}
