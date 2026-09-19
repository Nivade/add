<?php

declare(strict_types=1);

namespace App\Support\Ai;

use App\Enums\Ai\AiOperation;
use App\Support\Ai\Schemas\DecomposeIntentionSchema;
use App\Support\Ai\Schemas\ParseCaptureSchema;
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

    public static function parseCapture(string $user): self
    {
        return new self(
            operation: AiOperation::ParseCapture,
            system: Prompts::PARSE_CAPTURE,
            user: $user,
            schema: ParseCaptureSchema::builder(),
            promptVersion: Prompts::PARSE_CAPTURE_VERSION,
            schemaVersion: ParseCaptureSchema::VERSION,
            maxOutputTokens: self::maxOutputTokens(),
        );
    }

    public static function decomposeIntention(string $user): self
    {
        return new self(
            operation: AiOperation::DecomposeIntention,
            system: Prompts::DECOMPOSE,
            user: $user,
            schema: DecomposeIntentionSchema::builder(),
            promptVersion: Prompts::DECOMPOSE_VERSION,
            schemaVersion: DecomposeIntentionSchema::VERSION,
            maxOutputTokens: self::maxOutputTokens(),
        );
    }

    /** The answer has the same shape as a decomposition, so it shares that schema and its parser. */
    public static function splitStep(string $user): self
    {
        return new self(
            operation: AiOperation::SplitStep,
            system: Prompts::SPLIT_STEP,
            user: $user,
            schema: DecomposeIntentionSchema::builder(),
            promptVersion: Prompts::SPLIT_STEP_VERSION,
            schemaVersion: DecomposeIntentionSchema::VERSION,
            maxOutputTokens: self::maxOutputTokens(),
        );
    }

    /** Prompt and schema versions are in the key, so editing either invalidates every stored answer. */
    public function cacheKey(): string
    {
        return $this->operation->value.'-'.substr(
            hash('sha256', $this->promptVersion.$this->schemaVersion.$this->user),
            0,
            32
        );
    }

    private static function maxOutputTokens(): int
    {
        return (int) config('ai.openai.max_output_tokens', 900);
    }
}
