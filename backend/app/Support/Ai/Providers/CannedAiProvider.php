<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Contracts\AiProvider;
use App\Data\Ai\AiResponseData;
use App\Enums\Ai\AiOperation;
use App\Support\Ai\AiRequest;
use Illuminate\Support\Str;

/**
 * Deterministic placeholder answers for clicking through the UI. Unlike the
 * fixture driver it accepts any input, because free text typed into a browser
 * has no pre-written answer and a hard error there is useless.
 */
final class CannedAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'canned';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function complete(AiRequest $request): AiResponseData
    {
        return new AiResponseData(
            payload: match ($request->operation) {
                AiOperation::ParseCapture => $this->parseCapture($request->user),
                AiOperation::DecomposeIntention => $this->decompose(),
                AiOperation::SplitStep => $this->split(),
            },
            provider: $this->name(),
            model: $this->name(),
        );
    }

    /** @return array<string, mixed> */
    private function parseCapture(string $user): array
    {
        $title = Str::of($user)->trim()->before("\n")->trim()->limit(80)->value();

        return [
            'title' => $title === '' ? 'Untitled' : $title,
            'why' => null,
            'deadline_at' => null,
            'needs_clarification' => false,
        ];
    }

    /**
     * Obeys the decomposition prompt's rules so the canned path exercises the same parser.
     *
     * @return array<string, mixed>
     */
    private function decompose(): array
    {
        return [
            'steps' => [
                ['title' => 'Put the thing you need on the desk.', 'estimated_seconds' => 60],
                ['title' => 'Open it.', 'estimated_seconds' => 30],
                ['title' => 'Write the first line.', 'estimated_seconds' => 300],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function split(): array
    {
        return [
            'steps' => [
                ['title' => 'Pick up one thing.', 'estimated_seconds' => 20],
                ['title' => 'Put it where it belongs.', 'estimated_seconds' => 40],
            ],
        ];
    }
}
