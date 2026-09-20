<?php

declare(strict_types=1);

namespace App\Support\Time;

use App\Contracts\Appointment;
use App\Data\BackwardsPlanData;
use Carbon\CarbonImmutable;

/** §23: a notification answers why it interrupted, what to do, why now, and what happens if it waits. */
final class ReminderLines
{
    /** @return list<string> */
    public static function for(Appointment $appointment, BackwardsPlanData $plan, CarbonImmutable $now): array
    {
        $first = $plan->rungs[0];
        $leave = $plan->rungs[count($plan->rungs) - 1];
        $minutesToLeave = (int) round($now->diffInMinutes(CarbonImmutable::parse($leave->at), absolute: false));

        return [
            $appointment->appointmentTitle().' is at '.$plan->deadlineClock.'.',
            $first->rung->startingWords(),
            $minutesToLeave > 0
                ? 'Leaving is '.$minutesToLeave.' minutes away.'
                : 'Leaving is what comes next.',
            'If this waits, leaving at '.$leave->clock.' waits with it.',
        ];
    }
}
