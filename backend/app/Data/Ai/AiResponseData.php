<?php

declare(strict_types=1);

namespace App\Data\Ai;

use Spatie\LaravelData\Data;

class AiResponseData extends Data
{
    /** @param  array<string, mixed>  $payload */
    public function __construct(
        public array $payload,
        public string $provider,
        public string $model,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?int $cachedInputTokens = null,
    ) {}
}
