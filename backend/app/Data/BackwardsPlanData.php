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

    /** The rung the person reaches first, furthest from the appointment. */
    public function firstRung(): PlanRungData
    {
        return $this->rungs[0];
    }

    /** The last rung before the appointment itself: leaving. */
    public function leaveRung(): PlanRungData
    {
        return $this->rungs[count($this->rungs) - 1];
    }
}
