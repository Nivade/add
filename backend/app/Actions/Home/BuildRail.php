<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Contracts\Appointment;
use App\Data\BackwardsPlanData;
use App\Data\PlanRungData;
use App\Data\RailData;
use App\Models\ExecutionSession;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use App\Support\Time\BackwardsPlan;
use App\Support\Time\NextAppointment;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

/** The rail is on every screen, so it is a shared prop rather than something each page fetches. */
final class BuildRail
{
    use AsObject;

    public function handle(User $user): RailData
    {
        $now = ResolutionContext::forUser($user)->now;

        $session = ExecutionSession::query()->where('user_id', $user->id)->running()->first();

        $next = NextAppointment::forUser($user, $now);
        $leave = $this->leaveBy($next, $now);

        return new RailData(
            nowMinute: $this->minuteOf($now),
            nowClock: $now->format('H:i'),
            sessionStartedMinute: $session instanceof ExecutionSession
                ? $this->minuteOf($session->started_at->setTimezone($now->getTimezone()))
                : null,
            // The mark worth drawing is when to leave, not when the thing starts.
            leaveByMinute: $leave instanceof PlanRungData ? $this->minuteOf(CarbonImmutable::parse($leave->at)) : null,
            leaveByClock: $leave?->clock,
            appointmentTitle: $leave instanceof PlanRungData ? $next?->appointmentTitle() : null,
        );
    }

    private function leaveBy(?Appointment $next, CarbonImmutable $now): ?PlanRungData
    {
        $plan = $next instanceof Appointment ? BackwardsPlan::for($next, $now) : null;

        return $plan instanceof BackwardsPlanData ? $plan->rungs[count($plan->rungs) - 1] : null;
    }

    private function minuteOf(CarbonImmutable $at): int
    {
        return $at->hour * 60 + $at->minute;
    }
}
