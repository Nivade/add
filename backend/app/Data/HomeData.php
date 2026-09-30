<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CheckInTopic;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The four bands of home, in order. `restCount` is reassurance and is never a list. */
#[TypeScript]
class HomeData extends Data
{
    /** @param  list<NeedsAttentionData>  $needsAttention */
    public function __construct(
        public ?NextActionData $rightNow,
        public bool $rightNowIsCommitment,
        public ?ExecutionStateData $session,
        public ?ComingUpData $comingUp,
        public ?ReminderData $reminder,
        public ?JustFinishedData $justFinished,
        public array $needsAttention,
        public int $restCount,
        public int $sortingCount,
        public bool $hasOpenCommitments,
        public ?CheckInTopic $checkIn,
    ) {}

    /** The step Start would begin; a running session is continued, not started. */
    public function startableStepSeconds(): ?int
    {
        return $this->session instanceof ExecutionStateData ? null : $this->rightNow?->step->estimatedSeconds;
    }
}
