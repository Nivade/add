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
                AiOperation::ClassifyIngestion => $this->classifyIngestion(),
            },
            provider: $this->name(),
            model: $this->name(),
        );
    }

    /** @return array<string, mixed> */
    private function parseCapture(string $user): array
    {
        // The message opens with the day and zone, and the person's own words follow the blank line.
        $title = Str::of($user)->after("\n\n")->trim()->before("\n")->trim()->limit(80)->value();

        return [
            'title' => $title === '' ? 'Untitled' : $title,
            'why' => null,
            'deadline_at' => null,
            'clarifying_question' => null,
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

    /** @return array<string, mixed> */
    private function classifyIngestion(): array
    {
        return [
            'actionable' => false,
            'title' => null,
            'why' => null,
            'deadline_at' => null,
            'estimated_seconds' => null,
        ];
    }
}
