<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class NextActionData extends Data
{
    /** @param  list<string>  $why */
    public function __construct(
        public StepData $step,
        public IntentionData $intention,
        public array $why,
    ) {}
}
