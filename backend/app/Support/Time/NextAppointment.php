<?php

declare(strict_types=1);

namespace App\Support\Time;

use App\Contracts\Appointment;
use App\Enums\IntentionStatus;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;

/** One question, two tables: the soonest real time constraint, whoever recorded it. */
final class NextAppointment
{
    public static function forUser(User $user, CarbonImmutable $now): ?Appointment
    {
        $intention = Intention::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [IntentionStatus::Captured, IntentionStatus::Active])
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '>=', $now)
            ->oldest('deadline_at')
            ->first();

        $event = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('starts_at', '>=', $now)
            ->oldest('starts_at')
            ->first();

        $candidates = array_values(array_filter([$intention, $event]));

        usort(
            $candidates,
            fn (Appointment $a, Appointment $b): int => $a->appointmentAt() <=> $b->appointmentAt(),
        );

        return $candidates[0] ?? null;
    }
}
