<?php

declare(strict_types=1);

namespace App\Support\NextAction;

use App\Contracts\Appointment;
use App\Data\BackwardsPlanData;
use App\Models\User;
use App\Support\Time\BackwardsPlan;
use App\Support\Time\NextAppointment;
use Carbon\CarbonImmutable;

/** $now carries the person's zone, so backwards planning from a deadline lands on their day. */
final readonly class ResolutionContext
{
    /** @param  int|null  $availableSeconds  until the next leave-by time, or null when the day is open */
    public function __construct(
        public CarbonImmutable $now,
        public ?int $availableSeconds = null,
    ) {}

    public static function forUser(User $user): self
    {
        $now = CarbonImmutable::now($user->timezone);

        return new self($now, self::untilLeaving($user, $now));
    }

    public function availableInWords(): ?string
    {
        if ($this->availableSeconds === null) {
            return null;
        }

        $minutes = intdiv($this->availableSeconds, 60);

        return $minutes < 1 ? 'less than a minute' : $minutes.' minutes';
    }

    private static function untilLeaving(User $user, CarbonImmutable $now): ?int
    {
        $next = NextAppointment::forUser($user, $now);

        if (! $next instanceof Appointment) {
            return null;
        }

        $plan = BackwardsPlan::for($next, $now);

        if (! $plan instanceof BackwardsPlanData) {
            return null;
        }

        $leaveAt = CarbonImmutable::parse($plan->rungs[count($plan->rungs) - 1]->at);

        return $leaveAt <= $now ? 0 : (int) $now->diffInSeconds($leaveAt, absolute: true);
    }
}
