<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Data\BackwardsPlanData;
use App\Data\RailData;
use App\Data\RailMarkData;
use App\Models\ExecutionSession;
use App\Models\User;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

/** The strip is on every screen, so it is a shared prop rather than something each page fetches. */
final class BuildRail
{
    use AsObject;

    public function handle(User $user, ?ResolutionContext $context = null, ?int $stepSeconds = null): RailData
    {
        $context ??= ResolutionContext::onTheClock($user);
        $now = $context->now;

        $session = $user->runningSession()->getResults();
        $marks = $this->marks($context);

        return new RailData(
            nowMinute: $this->minuteOf($now),
            sessionStartedMinute: $session instanceof ExecutionSession
                ? $this->minuteOf($session->started_at->setTimezone($now->getTimezone()))
                : null,
            stepSeconds: $stepSeconds,
            marks: $marks,
            appointmentTitle: $marks === [] ? null : $context->appointment?->appointmentTitle(),
        );
    }

    /** @return list<RailMarkData> */
    private function marks(ResolutionContext $context): array
    {
        $plan = $context->plan;
        $at = $context->appointment?->appointmentAt();

        if (! $plan instanceof BackwardsPlanData || ! $at instanceof CarbonImmutable) {
            return [];
        }

        $marks = [];

        foreach ($plan->rungs as $rung) {
            $local = $rung->instant()->setTimezone($context->now->getTimezone());

            if ($local->isSameDay($context->now)) {
                $marks[] = new RailMarkData($rung->rung, $this->minuteOf($local));
            }
        }

        // A plan only exists for an appointment today, so the appointment itself always has a mark.
        $marks[] = new RailMarkData(null, $this->minuteOf($at->setTimezone($context->now->getTimezone())));

        return $marks;
    }

    private function minuteOf(CarbonImmutable $at): int
    {
        return $at->hour * 60 + $at->minute;
    }
}
