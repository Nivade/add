<?php

declare(strict_types=1);

namespace App\Support\Ai;

use App\Enums\Ai\AiOperation;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/** $system is byte-stable and cacheable by the provider; $user is what differs per call. */
final readonly class AiRequest
{
    /** @param  Closure(JsonSchema):array<string, mixed>  $schema */
    public function __construct(
        public AiOperation $operation,
        public string $system,
        public string $user,
        public Closure $schema,
        public string $promptVersion,
        public string $schemaVersion,
        public int $maxOutputTokens,
    ) {}

    /** Prompt and schema versions are in the key, so editing either invalidates every stored answer. */
    public function cacheKey(): string
    {
        return $this->operation->value.'-'.substr(
            hash('sha256', $this->promptVersion.$this->schemaVersion.$this->user),
            0,
            32
        );
    }
}
