<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;

/**
 * One method, one call: a payload is never sent twice to ask two questions
 * about it. A new feature gets a new AiOperation, not a second method here.
 */
interface AiProvider
{
    public function name(): string;

    public function isAvailable(): bool;

    public function complete(AiRequest $request): AiResponseData;
}
