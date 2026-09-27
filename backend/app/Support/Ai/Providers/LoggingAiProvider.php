<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Logs the shape of every call off this machine, never its text. */
final class LoggingAiProvider implements AiProvider
{
    public function __construct(public readonly AiProvider $inner) {}

    public function name(): string
    {
        return $this->inner->name();
    }

    public function isAvailable(): bool
    {
        return $this->inner->isAvailable();
    }

    public function complete(AiRequest $request): AiResponseData
    {
        $startedAt = microtime(true);

        try {
            $response = $this->inner->complete($request);
        } catch (Throwable $throwable) {
            Log::warning('AI call failed.', [
                ...$this->context($request, $startedAt),
                'exception' => $throwable::class,
            ]);

            throw $throwable;
        }

        Log::info('AI call completed.', [
            ...$this->context($request, $startedAt),
            'model' => $response->model,
            'input_tokens' => $response->inputTokens,
            'output_tokens' => $response->outputTokens,
            'cached_input_tokens' => $response->cachedInputTokens,
        ]);

        return $response;
    }

    /** @return array<string, mixed> */
    private function context(AiRequest $request, float $startedAt): array
    {
        return [
            'operation' => $request->operation->value,
            'provider' => $this->inner->name(),
            'prompt_version' => $request->promptVersion,
            'schema_version' => $request->schemaVersion,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
