<?php

declare(strict_types=1);

namespace App\Support\Time;

use App\Contracts\Appointment;
use App\Data\BackwardsPlanData;
use App\Data\PlanRungData;
use App\Enums\PlanRung;
use Carbon\CarbonImmutable;

/** Arithmetic, never a model: a travel-time guess presented as fact is how the app starts lying. */
final class BackwardsPlan
{
    public static function for(Appointment $appointment, CarbonImmutable $now): ?BackwardsPlanData
    {
        $at = $appointment->appointmentAt()?->setTimezone($now->getTimezone());

        if (! $at instanceof CarbonImmutable || ! $at->isSameDay($now)) {
            return null;
        }

        $rungAt = $at;
        $rungs = [];

        foreach ([PlanRung::Leave, PlanRung::GetReady, PlanRung::FindThings] as $rung) {
            $stated = $appointment->statedSeconds($rung);
            $seconds = $stated ?? $rung->assumedSeconds();
            $rungAt = $rungAt->subSeconds($seconds);

            $rungs[] = new PlanRungData(
                $rung,
                $rungAt->toIso8601String(),
                $rungAt->format('H:i'),
                $seconds,
                $stated === null,
                $rungAt < $now,
            );
        }

        return new BackwardsPlanData(
            $appointment->appointmentKind(),
            $appointment->appointmentId(),
            $at->format('H:i'),
            array_reverse($rungs),
        );
    }
}
