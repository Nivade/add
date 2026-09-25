<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Models\User;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiUnavailable;

/** Wraps a driver that leaves the machine. Without consent this answers exactly as it would with no key, never a degraded try. */
final class ConsentGatedAiProvider implements AiProvider
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
        throw_unless(
            User::query()->whereKey($request->userId)->value('ai_consented_at') !== null,
            AiUnavailable::class,
            'AI is disabled; no consent is on record for '.$request->operation->value.'.'
        );

        return $this->inner->complete($request);
    }
}
