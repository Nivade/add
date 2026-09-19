<?php

declare(strict_types=1);

namespace App\Data\Ai;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ParsedCaptureData extends Data
{
    public function __construct(
        public string $title,
        public ?string $why,
        public ?CarbonImmutable $deadlineAt,
        public bool $needsClarification,
    ) {}
}
