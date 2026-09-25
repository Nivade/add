<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Data\PlanRungData;
use App\Data\RailData;
use App\Models\ExecutionSession;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

/** The rail is on every screen, so it is a shared prop rather than something each page fetches. */
final class BuildRail
{
    use AsObject;

    public function handle(User $user, ?ResolutionContext $context = null): RailData
    {
        $context ??= ResolutionContext::forUser($user);
        $now = $context->now;

        $session = $user->runningSession()->getResults();
        $leave = $context->plan?->leaveRung();

        return new RailData(
            nowMinute: $this->minuteOf($now),
            sessionStartedMinute: $session instanceof ExecutionSession
                ? $this->minuteOf($session->started_at->setTimezone($now->getTimezone()))
                : null,
            // The mark worth drawing is when to leave, not when the thing starts.
            leaveByMinute: $leave instanceof PlanRungData ? $this->minuteOf($leave->instant()) : null,
            leaveByClock: $leave?->clock,
            appointmentTitle: $leave instanceof PlanRungData ? $context->appointment?->appointmentTitle() : null,
        );
    }

    private function minuteOf(CarbonImmutable $at): int
    {
        return $at->hour * 60 + $at->minute;
    }
}
