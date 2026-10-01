<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Contracts\NextActionResolver;
use App\Data\FinishedData;
use App\Data\IntentionData;
use App\Enums\ExecutionEventType;
use App\Enums\IntentionStatus;
use App\Enums\StepStatus;
use App\Models\ExecutionEvent;
use App\Models\ExecutionSession;
use App\Models\Intention;
use App\Models\Step;
use App\Support\NextAction\ResolutionContext;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Lorisleiva\Actions\Concerns\AsObject;

/** Each line is a fact that is true about this intention, stated once, and left out when it is not. */
final class BuildFinished
{
    use AsObject;

    /** Under a day on the list is not worth saying; a day is still "today" to most people. */
    private const int ON_THE_LIST_FROM_DAYS = 2;

    public function __construct(private readonly NextActionResolver $resolver) {}

    public function handle(ExecutionSession $session): FinishedData
    {
        $session->loadMissing(['intention.steps', 'intention.sessions.events', 'intention.recurrenceTemplate', 'user']);
        $intention = $session->intention;
        $user = $session->user;
        $finishedAt = $intention->completed_at ?? $session->ended_at ?? $user->now();

        $lines = array_values(array_filter([
            $this->stepsLine($intention),
            $this->workLine($intention, $user->now()),
            $this->deadlineLine($intention, $finishedAt),
            $this->onTheListLine($intention, $finishedAt),
            $this->todayLine($session),
        ]));

        return new FinishedData(
            IntentionData::from($intention),
            $lines,
            $this->resolver->resolve($user, ResolutionContext::forUser($user)),
            $intention->repeatsEveryDays(),
        );
    }

    private function stepsLine(Intention $intention): ?string
    {
        $done = $intention->steps->where('status', StepStatus::Done)->count();

        if ($done === 0) {
            return null;
        }

        $skipped = $intention->steps->filter(fn (Step $step): bool => $step->skip_count > 0)->count();
        $line = $done.' '.($done === 1 ? 'step' : 'steps').' done';

        return $skipped === 0 ? $line.'.' : $line.', '.$skipped.' skipped along the way.';
    }

    private function workLine(Intention $intention, CarbonImmutable $now): ?string
    {
        $seconds = $intention->sessions->sum(fn (ExecutionSession $session): int => $this->workedSeconds($session, $now));

        if ($seconds < 60) {
            return null;
        }

        return 'About '.CarbonInterval::seconds($seconds)->cascade()->forHumans(parts: 1).' of work.';
    }

    /** A pause starts at Paused or Distracted and ends at Resumed, or at the session's end. */
    private function workedSeconds(ExecutionSession $session, CarbonImmutable $now): int
    {
        $end = $session->ended_at ?? $now;
        $paused = 0;
        $pausedSince = null;

        foreach ($session->events->sortBy('id') as $event) {
            /** @var ExecutionEvent $event */
            if (in_array($event->type, [ExecutionEventType::Paused, ExecutionEventType::Distracted], true)) {
                $pausedSince ??= $event->created_at;
            } elseif ($event->type === ExecutionEventType::Resumed && $pausedSince !== null) {
                $paused += (int) $pausedSince->diffInSeconds($event->created_at, absolute: true);
                $pausedSince = null;
            }
        }

        if ($pausedSince !== null) {
            $paused += (int) $pausedSince->diffInSeconds($end, absolute: true);
        }

        return max(0, (int) $session->started_at->diffInSeconds($end, absolute: true) - $paused);
    }

    private function deadlineLine(Intention $intention, CarbonImmutable $finishedAt): ?string
    {
        if ($intention->deadline_at === null || $intention->deadline_at->lte($finishedAt)) {
            return null;
        }

        $words = CarbonInterval::seconds((int) $finishedAt->diffInSeconds($intention->deadline_at, absolute: true))
            ->cascade()
            ->forHumans(parts: 1);

        return ucfirst($words).' before the deadline.';
    }

    private function onTheListLine(Intention $intention, CarbonImmutable $finishedAt): ?string
    {
        if ($intention->created_at === null) {
            return null;
        }

        $days = (int) floor($intention->created_at->diffInDays($finishedAt, absolute: true));

        return $days >= self::ON_THE_LIST_FROM_DAYS ? 'It had been on your list for '.$days.' days.' : null;
    }

    private function todayLine(ExecutionSession $session): ?string
    {
        $finished = Intention::query()
            ->where('user_id', $session->user_id)
            ->where('status', IntentionStatus::Done)
            ->where('completed_at', '>=', $session->user->now()->startOfDay())
            ->count();

        return $finished > 0 ? $finished.' '.($finished === 1 ? 'thing' : 'things').' finished today.' : null;
    }
}
