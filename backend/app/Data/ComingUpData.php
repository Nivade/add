<?php

declare(strict_types=1);

namespace App\Data;

use App\Contracts\Appointment;
use App\Enums\AppointmentKind;
use App\Support\Time\BackwardsPlan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The next real time constraint, already phrased, so no client does date arithmetic. */
#[TypeScript]
class ComingUpData extends Data
{
    public function __construct(
        public AppointmentKind $kind,
        public string $id,
        public string $title,
        public string $inWords,
        public bool $inferred,
        public ?BackwardsPlanData $plan,
    ) {}

    public static function of(Appointment $appointment, CarbonImmutable $now): ?self
    {
        $at = $appointment->appointmentAt();

        if (! $at instanceof CarbonImmutable) {
            return null;
        }

        return new self(
            $appointment->appointmentKind(),
            $appointment->appointmentId(),
            $appointment->appointmentTitle(),
            $at->setTimezone($now->getTimezone())->diffForHumans([
                'other' => $now,
                'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
            ]),
            $appointment->appointmentInferred(),
            BackwardsPlan::for($appointment, $now),
        );
    }
}
