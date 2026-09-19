<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** What the rail draws: where now sits in the person's day, and the two marks worth putting on it. */
#[TypeScript]
class RailData extends Data
{
    public function __construct(
        public string $nowAt,
        public int $minuteOfDay,
        public ?string $sessionStartedAt,
        public ?string $deadlineAt,
        public ?string $deadlineTitle,
    ) {}
}
