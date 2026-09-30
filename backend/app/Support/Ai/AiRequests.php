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
        return self::make(AiOperation::ParseCapture, Prompts::PARSE_CAPTURE, Prompts::PARSE_CAPTURE_VERSION, ParseCaptureSchema::class, $userId, implode("\n", ['Today is '.$now->format('l j F Y').' in '.$now->getTimezone()->getName().'.', '', $capture]));
    }

    public static function decomposeIntention(?int $userId, string $user): AiRequest
    {
        return self::make(AiOperation::DecomposeIntention, Prompts::DECOMPOSE, Prompts::DECOMPOSE_VERSION, DecomposeIntentionSchema::class, $userId, $user);
    }

    /** The answer has the same shape as a decomposition, so it shares that schema and its parser. */
    public static function splitStep(int $userId, string $user): AiRequest
    {
        return self::make(AiOperation::SplitStep, Prompts::SPLIT_STEP, Prompts::SPLIT_STEP_VERSION, DecomposeIntentionSchema::class, $userId, $user);
    }

    public static function classifyIngestion(int $userId, string $user): AiRequest
    {
        return self::make(AiOperation::ClassifyIngestion, Prompts::CLASSIFY_INGESTION, Prompts::CLASSIFY_INGESTION_VERSION, ClassifyIngestionSchema::class, $userId, $user);
    }

    /** @param  class-string<ClassifyIngestionSchema|DecomposeIntentionSchema|ParseCaptureSchema>  $schema */
    private static function make(AiOperation $operation, string $system, string $promptVersion, string $schema, ?int $userId, string $user): AiRequest
    {
        return new AiRequest(
            system: $system,
            user: $user,
            schema: $schema::builder(),
            promptVersion: $promptVersion,
            schemaVersion: $schema::VERSION,
            maxOutputTokens: (int) config('ai-toolkit.openai.max_output_tokens', 900),
            rateLimitScope: $userId === null ? null : (string) $userId,
            operation: $operation->value,
        );
    }
}
