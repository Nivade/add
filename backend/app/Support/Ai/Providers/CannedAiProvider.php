<?php

declare(strict_types=1);

namespace App\Support\Ai\Providers;

use App\Attributes\Driver;
use App\Enums\Ai\AiOperation;
use App\Enums\CaptureKind;
use App\Support\Concerns\NamedByDriver;
use Illuminate\Support\Str;
use Nvade\AiToolkit\AiRequest;
use Nvade\AiToolkit\AiResponse;
use Nvade\AiToolkit\Contracts\AiProvider;

/** Placeholder answers for any input, for clicking through the UI; never scored. */
#[Driver('canned')]
final class CannedAiProvider implements AiProvider
{
    use NamedByDriver;

    public function isAvailable(): bool
    {
        return true;
    }

    public function respond(AiRequest $request): AiResponse
    {
        return new AiResponse(
            payload: match (AiOperation::from((string) $request->operation)) {
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
            'kind' => $this->kindOf($title)->value,
            'title' => $title === '' ? 'Untitled' : $title,
            'why' => null,
            'deadline_at' => null,
            'clarifying_question' => null,
            'waiting_on' => null,
        ];
    }

    private function kindOf(string $capture): CaptureKind
    {
        $opening = Str::lower($capture);

        return match (true) {
            Str::startsWith($opening, ['waiting for', 'waiting on']) => CaptureKind::WaitingFor,
            Str::startsWith($opening, ["i'll", 'i will']) => CaptureKind::Promise,
            Str::startsWith($opening, 'remind me') => CaptureKind::Reminder,
            default => CaptureKind::Thought,
        };
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
                ['title' => 'Put the thing you need on the desk.', 'estimated_seconds' => 60, 'place' => null],
                ['title' => 'Open it.', 'estimated_seconds' => 30, 'place' => null],
                ['title' => 'Write the first line.', 'estimated_seconds' => 300, 'place' => 'computer'],
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
