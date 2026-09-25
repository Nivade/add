<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiProviderRequestFailed;
use App\Support\Ai\Exceptions\AiRateLimited;
use App\Support\Ai\Exceptions\AiUnavailable;
use App\Support\Ai\StructuredAgent;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\StructuredAgentResponse;
use LogicException;
use Throwable;

final class OpenAiProvider implements AiProvider
{
    private const string RATE_LIMIT_KEY = 'ai-openai';

    public function name(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return filled(config('ai.openai.api_key'));
    }

    public function complete(AiRequest $request): AiResponseData
    {
        throw_unless($this->isAvailable(), AiUnavailable::class, 'OpenAI provider called without ai.openai.api_key configured.');

        $this->guardRateLimit($request);

        $model = (string) config('ai.openai.model');

        $agent = new StructuredAgent(
            instructions: $request->system,
            schema: $request->schema,
            maxOutputTokens: $request->maxOutputTokens,
            reasoningEffort: (string) config('ai.openai.reasoning_effort'),
        );

        try {
            $response = $agent->prompt(
                $request->user,
                provider: Lab::OpenAI,
                model: $model,
                timeout: (int) config('ai.openai.timeout'),
            );
        } catch (Throwable $exception) {
            throw new AiProviderRequestFailed('OpenAI request failed: '.$exception::class);
        }

        if (! $response instanceof StructuredAgentResponse) {
            throw new LogicException('StructuredAgent must always receive a StructuredAgentResponse.');
        }

        return new AiResponseData(
            payload: $response->toArray(),
            provider: $this->name(),
            model: $response->meta->model ?? $model,
            inputTokens: $response->usage->promptTokens,
            outputTokens: $response->usage->completionTokens,
            cachedInputTokens: $response->usage->cacheReadInputTokens,
        );
    }

    /** laravel/ai does not throttle itself, so this is the only place a burst is stopped. Keyed per person so one burst cannot lock everyone else out. */
    private function guardRateLimit(AiRequest $request): void
    {
        $maxAttempts = (int) config('ai.openai.rate_limit.max_attempts');
        $decaySeconds = (int) config('ai.openai.rate_limit.decay_seconds');
        $key = self::RATE_LIMIT_KEY.'-'.$request->userId;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new AiRateLimited("OpenAI provider rate limit exceeded ({$maxAttempts} calls per {$decaySeconds}s).");
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
