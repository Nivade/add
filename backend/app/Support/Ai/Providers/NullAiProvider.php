<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiUnavailable;

/** AI disabled. Fails loudly rather than degrading into a silent empty answer. */
final class NullAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function complete(AiRequest $request): AiResponseData
    {
        throw new AiUnavailable('AI is disabled; no provider is configured to answer '.$request->operation->value.'.');
    }
}
