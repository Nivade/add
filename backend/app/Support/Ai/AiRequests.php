<?php

declare(strict_types=1);

namespace App\Support\Ai;

use App\Enums\Ai\AiOperation;
use App\Support\Ai\Schemas\ClassifyIngestionSchema;
use App\Support\Ai\Schemas\DecomposeIntentionSchema;
use App\Support\Ai\Schemas\ParseCaptureSchema;
use Carbon\CarbonImmutable;
use Nvade\AiToolkit\AiRequest;

/** The asking person rides in rateLimitScope, which both the throttle and the consent gate key on. */
final class AiRequests
{
    /** A model with no date cannot resolve "Saturday", and one with no zone answers on the wrong clock. */
    public static function parseCapture(?int $userId, string $capture, CarbonImmutable $now): AiRequest
    {
        return new AiRequest(
            system: Prompts::PARSE_CAPTURE,
            user: implode("\n", ['Today is '.$now->format('l j F Y').' in '.$now->getTimezone()->getName().'.', '', $capture]),
            schema: ParseCaptureSchema::builder(),
            promptVersion: Prompts::PARSE_CAPTURE_VERSION,
            schemaVersion: ParseCaptureSchema::VERSION,
            maxOutputTokens: self::maxOutputTokens(),
            rateLimitScope: self::scope($userId),
            operation: AiOperation::ParseCapture->value,
        );
    }

    public static function decomposeIntention(?int $userId, string $user): AiRequest
    {
        return new AiRequest(
            system: Prompts::DECOMPOSE,
            user: $user,
            schema: DecomposeIntentionSchema::builder(),
            promptVersion: Prompts::DECOMPOSE_VERSION,
            schemaVersion: DecomposeIntentionSchema::VERSION,
            maxOutputTokens: self::maxOutputTokens(),
            rateLimitScope: self::scope($userId),
            operation: AiOperation::DecomposeIntention->value,
        );
    }

    /** The answer has the same shape as a decomposition, so it shares that schema and its parser. */
    public static function splitStep(int $userId, string $user): AiRequest
    {
        return new AiRequest(
            system: Prompts::SPLIT_STEP,
            user: $user,
            schema: DecomposeIntentionSchema::builder(),
            promptVersion: Prompts::SPLIT_STEP_VERSION,
            schemaVersion: DecomposeIntentionSchema::VERSION,
            maxOutputTokens: self::maxOutputTokens(),
            rateLimitScope: self::scope($userId),
            operation: AiOperation::SplitStep->value,
        );
    }

    public static function classifyIngestion(int $userId, string $user): AiRequest
    {
        return new AiRequest(
            system: Prompts::CLASSIFY_INGESTION,
            user: $user,
            schema: ClassifyIngestionSchema::builder(),
            promptVersion: Prompts::CLASSIFY_INGESTION_VERSION,
            schemaVersion: ClassifyIngestionSchema::VERSION,
            maxOutputTokens: self::maxOutputTokens(),
            rateLimitScope: self::scope($userId),
            operation: AiOperation::ClassifyIngestion->value,
        );
    }

    private static function scope(?int $userId): ?string
    {
        return $userId === null ? null : (string) $userId;
    }

    private static function maxOutputTokens(): int
    {
        return (int) config('ai-toolkit.openai.max_output_tokens', 900);
    }
}
