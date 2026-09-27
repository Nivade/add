<?php

declare(strict_types=1);

namespace App\Actions\Intentions;

use App\Contracts\DeadlineExtractor;
use App\Models\Intention;
use App\Support\Time\ExtractedDeadline;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

/** The answer lands on the intention it was asked about, so the capture it came from stays its origin. */
final class ClarifyIntention
{
    use AsObject;

    public function __construct(private readonly DeadlineExtractor $extractor) {}

    public function handle(Intention $intention, string $answer): Intention
    {
        $answered = Intention::query()
            ->whereKey($intention->id)
            ->awaitingClarification()
            ->update(['clarification' => $answer]);

        if ($answered === 0) {
            throw ValidationException::withMessages(['answer' => __('This has already been answered.')]);
        }

        $intention->refresh();

        $this->inferDeadline($intention, $answer);

        DecomposeIntention::dispatch($intention);

        return $intention;
    }

    /** Left unconfirmed so the person is asked about it, and never over a date they already gave. */
    private function inferDeadline(Intention $intention, string $answer): void
    {
        if ($intention->deadline_at !== null) {
            return;
        }

        $extracted = $this->extractor->extract($answer, CarbonImmutable::now($intention->user()->sole()->timezone));

        if ($extracted instanceof ExtractedDeadline) {
            $intention->update(['deadline_at' => $extracted->at]);
        }
    }
}
