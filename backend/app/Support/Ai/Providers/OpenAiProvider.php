<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Attributes\Driver;
use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiProviderRequestFailed;
use App\Support\Ai\Exceptions\AiRateLimited;
use App\Support\Ai\Exceptions\AiUnavailable;
use App\Support\Concerns\NamedByDriver;
use Nvade\AiToolkit\AiRequest as ToolkitRequest;
use Nvade\AiToolkit\Exceptions\AiProviderRequestFailed as ToolkitRequestFailed;
use Nvade\AiToolkit\Exceptions\AiRateLimited as ToolkitRateLimited;
use Nvade\AiToolkit\Exceptions\AiUnavailable as ToolkitUnavailable;
use Nvade\AiToolkit\Providers\OpenAiProvider as ToolkitOpenAiProvider;

/** Throttled per person, so one burst cannot lock everyone else out; failures come back as this app's exceptions. */
#[Driver('openai')]
final class OpenAiProvider implements AiProvider
{
    use NamedByDriver;

    public function __construct(private readonly ToolkitOpenAiProvider $openAi) {}

    public function isAvailable(): bool
    {
        return $this->openAi->isAvailable();
    }

    public function complete(AiRequest $request): AiResponseData
    {
        try {
            $response = $this->openAi->respond(new ToolkitRequest(
                system: $request->system,
                user: $request->user,
                schema: $request->schema,
                promptVersion: $request->promptVersion,
                schemaVersion: $request->schemaVersion,
                maxOutputTokens: $request->maxOutputTokens,
                rateLimitScope: (string) $request->userId,
            ));
        } catch (ToolkitUnavailable $unavailable) {
            throw new AiUnavailable($unavailable->getMessage());
        } catch (ToolkitRateLimited $rateLimited) {
            throw new AiRateLimited($rateLimited->getMessage(), previous: $rateLimited);
        } catch (ToolkitRequestFailed $failed) {
            $cause = $failed->getPrevious() ?? $failed;

            throw new AiProviderRequestFailed('OpenAI request failed: '.$cause::class, previous: $cause);
        }

        return new AiResponseData(
            payload: $response->payload,
            provider: $this->name(),
            model: $response->model,
            inputTokens: $response->inputTokens,
            outputTokens: $response->outputTokens,
            cachedInputTokens: $response->cachedInputTokens,
        );
    }
}
