<?php

declare(strict_types=1);

namespace App\Data\Ai;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class IngestionClassificationData extends Data
{
    public function __construct(
        public bool $actionable,
        public ?string $title,
        public ?string $why,
        public ?CarbonImmutable $deadlineAt,
        public ?int $estimatedSeconds,
    ) {}
}
