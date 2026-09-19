<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The next real time constraint, already phrased, so no client does date arithmetic. */
#[TypeScript]
class ComingUpData extends Data
{
    public function __construct(
        public IntentionData $intention,
        public string $inWords,
    ) {}
}
