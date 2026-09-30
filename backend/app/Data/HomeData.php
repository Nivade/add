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
        public bool $hasOpenCommitments,
        public ?CheckInTopic $checkIn,
    ) {}
}
