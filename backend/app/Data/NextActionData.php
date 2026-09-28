<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Attributes\OneThing;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[OneThing]
class NextActionData extends Data
{
    /** @param  list<string>  $why */
    public function __construct(
        public StepData $step,
        public IntentionData $intention,
        public array $why,
    ) {}
}
