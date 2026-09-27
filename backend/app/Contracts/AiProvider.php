<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;

/** One method: a new question is a new AiOperation, never a second method or a second send. */
interface AiProvider
{
    public function name(): string;

    public function isAvailable(): bool;

    public function complete(AiRequest $request): AiResponseData;
}
