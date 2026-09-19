<?php

declare(strict_types=1);

namespace App\Support\Ai;

use Closure;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\StructuredAnonymousAgent;

/** #[Strict] forces json_schema strict mode, so a violation is rejected rather than returned as prose. */
#[Strict]
final class StructuredAgent extends StructuredAnonymousAgent implements HasProviderOptions
{
    /**
     * @param  iterable<mixed>  $messages
     * @param  iterable<mixed>  $tools
     */
    public function __construct(
        string $instructions,
        iterable $messages = [],
        iterable $tools = [],
        ?Closure $schema = null,
        private readonly int $maxOutputTokens = 900,
        private readonly ?string $reasoningEffort = null,
    ) {
        parent::__construct($instructions, $messages, $tools, $schema);
    }

    public function maxTokens(): int
    {
        return $this->maxOutputTokens;
    }

    /** @return array<string, mixed> */
    public function providerOptions(Lab|string $provider): array
    {
        if (! is_string($this->reasoningEffort) || $this->reasoningEffort === '') {
            return [];
        }

        return match ($provider) {
            Lab::OpenAI => ['reasoning' => ['effort' => $this->reasoningEffort]],
            default => [],
        };
    }
}
