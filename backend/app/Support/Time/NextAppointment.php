<?php

declare(strict_types=1);

namespace App\Support\Time;

use App\Contracts\Appointment;
use App\Models\CalendarEvent;
use App\Models\Intention;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/** One question, two tables: the soonest real time constraint, whoever recorded it. */
final class NextAppointment
{
    public static function forUser(User $user, CarbonImmutable $now): ?Appointment
    {
        return self::sorted($user, $now, null, 1)[0] ?? null;
    }

    /**
     * @param  CarbonImmutable  $until  the far edge of the window
     * @return list<Appointment>
     */
    public static function upcomingForUser(User $user, CarbonImmutable $now, CarbonImmutable $until): array
    {
        return self::sorted($user, $now, $until, null);
    }

    /** @return list<Appointment> */
    private static function sorted(User $user, CarbonImmutable $now, ?CarbonImmutable $until, ?int $limit): array
    {
        // Columns hold UTC, and a binding is written as its wall clock, so both ends are converted first.
        $now = $now->utc();
        $until = $until?->utc();

        $intentions = Intention::query()
            ->where('user_id', $user->id)
            ->open()
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '>=', $now)
            ->when($until, fn (Builder $query): Builder => $query->where('deadline_at', '<=', $until))
            ->oldest('deadline_at')
            ->limit($limit)
            ->get();

        $events = CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('starts_at', '>=', $now)
            ->when($until, fn (Builder $query): Builder => $query->where('starts_at', '<=', $until))
            ->oldest('starts_at')
            ->limit($limit)
            ->get();

        $candidates = [...$intentions->all(), ...$events->all()];

        usort(
            $candidates,
            fn (Appointment $a, Appointment $b): int => $a->appointmentAt() <=> $b->appointmentAt(),
        );

        return $candidates;
    }
}
