<?php

declare(strict_types=1);

namespace App\Data;

use App\Attributes\OneThing;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The closing screen: what was handled, what it took, and at most one thing next. */
#[TypeScript]
#[OneThing]
class FinishedData extends Data
{
    /** @param  list<string>  $lines */
    public function __construct(
        public IntentionData $intention,
        public array $lines,
        public ?NextActionData $next,
        public ?int $recurrenceEveryDays,
    ) {}
}
