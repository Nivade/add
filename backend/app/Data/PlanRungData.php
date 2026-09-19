<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PlanRung;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One rung of a backwards plan. `assumed` is true until the person says otherwise. */
#[TypeScript]
class PlanRungData extends Data
{
    public function __construct(
        public PlanRung $rung,
        public string $at,
        public string $clock,
        public int $seconds,
        public bool $assumed,
        public bool $alreadyPassed,
    ) {}
}
