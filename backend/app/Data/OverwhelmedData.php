<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One step and a count of the rest. `restCount` is reassurance and is never a list. */
#[TypeScript]
class OverwhelmedData extends Data
{
    public function __construct(
        public ?NextActionData $smallestStep,
        public int $restCount,
    ) {}
}
