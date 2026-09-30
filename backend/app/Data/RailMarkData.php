<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PlanRung;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A tick on the day strip; a null rung is the appointment itself. */
#[TypeScript]
class RailMarkData extends Data
{
    public function __construct(
        public ?PlanRung $rung,
        public int $minute,
        public string $clock,
    ) {}
}
