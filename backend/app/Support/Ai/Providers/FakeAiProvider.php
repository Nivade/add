<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiUnavailable;

/** Queued answers for tests. Unlike the fixture provider it needs no files on disk. */
final class FakeAiProvider implements AiProvider
{
    /** @var list<array<string, mixed>> */
    private array $queue = [];

    /** @var list<AiRequest> */
    public array $received = [];

    /** @param  array<string, mixed>  $payload */
    public function push(array $payload): self
    {
        $this->queue[] = $payload;

        return $this;
    }

    public function name(): string
    {
        return 'fake';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function complete(AiRequest $request): AiResponseData
    {
        $this->received[] = $request;

        if ($this->queue === []) {
            throw new AiUnavailable('FakeAiProvider has no queued answer for '.$request->operation->value.'.');
        }

        return new AiResponseData(
            payload: array_shift($this->queue),
            provider: $this->name(),
            model: $this->name(),
        );
    }
}
