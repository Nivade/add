<?php

declare(strict_types=1);

namespace App\Data;

use App\Attributes\OneThing;
use App\Data\Concerns\AnswersOk;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** What execution mode renders: one step, the intention it belongs to, and progress nobody wrote by hand. */
#[TypeScript]
#[OneThing]
class ExecutionStateData extends Data
{
    use AnswersOk;

    /** @param  list<string>  $progress */
    public function __construct(
        public ExecutionSessionData $session,
        public IntentionData $intention,
        public array $progress,
        public string $elapsed,
        public bool $currentStepIsCommitment,
        public bool $returning,
        public int $stepsDone,
        public ?string $notice,
    ) {}
}
