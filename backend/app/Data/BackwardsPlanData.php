<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\AppointmentKind;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Counted backwards from a real appointment, in the person's zone, never by a model. */
#[TypeScript]
class BackwardsPlanData extends Data
{
    /** @param  list<PlanRungData>  $rungs */
    public function __construct(
        public AppointmentKind $kind,
        public string $appointmentId,
        public string $deadlineClock,
        public array $rungs,
    ) {}
}
