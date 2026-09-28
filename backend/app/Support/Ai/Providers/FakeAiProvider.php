<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Attributes\Driver;
use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Support\Ai\AiRequest;
use App\Support\Ai\Exceptions\AiUnavailable;
use App\Support\Concerns\NamedByDriver;

/** Queued answers for tests. Unlike the fixture provider it needs no files on disk. */
#[Driver('fake')]
final class FakeAiProvider implements AiProvider
{
    use NamedByDriver;

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
