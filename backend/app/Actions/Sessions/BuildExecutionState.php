<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Data\ExecutionSessionData;
use App\Data\ExecutionStateData;
use App\Data\IntentionData;
use App\Enums\StepStatus;
use App\Models\ExecutionSession;
use App\Models\Step;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Database\Query\Builder;
use Lorisleiva\Actions\Concerns\AsObject;

/** What execution mode renders: one step, the intention it belongs to, and progress nobody wrote by hand. */
final class BuildExecutionState
{
    use AsObject;

    /** Below this, "a minute or two" is truer than a number. */
    private const int ELAPSED_FLOOR_SECONDS = 90;

    public function handle(ExecutionSession $session): ExecutionStateData
    {
        $session->refresh()->load(['currentStep', 'intention.steps', 'user']);

        return new ExecutionStateData(
            ExecutionSessionData::from($session),
            IntentionData::from($session->intention),
            $this->progressLines($session),
            $this->elapsedWords($session),
        );
    }

    /** @return list<string> */
    private function progressLines(ExecutionSession $session): array
    {
        $lines = [];

        $steps = $session->intention->steps;
        $done = $steps->where('status', StepStatus::Done)->count();

        if ($steps->isNotEmpty()) {
            $lines[] = $done.' of '.$steps->count().' steps done.';
        }

        if ($session->steps_completed > 0) {
            $lines[] = $session->steps_completed.' '
                .($session->steps_completed === 1 ? 'step' : 'steps')
                .' done in this sitting.';
        }

        $today = $this->doneToday($session);

        if ($today > 0) {
            $lines[] = $today.' '.($today === 1 ? 'thing' : 'things').' finished today.';
        }

        return $lines;
    }

    private function doneToday(ExecutionSession $session): int
    {
        $midnight = $session->user->now()->startOfDay();

        return Step::query()
            ->where('status', StepStatus::Done)
            ->where('completed_at', '>=', $midnight)
            ->whereHas('intention', fn (Builder $intention) => $intention->where('user_id', $session->user_id))
            ->count();
    }

    /** §13: elapsed time stated in words, because a running clock is a countdown by another name. */
    private function elapsedWords(ExecutionSession $session): string
    {
        $now = $session->user->now();
        $until = $session->paused_at ?? $session->ended_at ?? $now;
        $seconds = (int) $session->started_at->diffInSeconds($until, absolute: true);

        if ($seconds < self::ELAPSED_FLOOR_SECONDS) {
            return 'You have just started.';
        }

        $words = CarbonInterval::seconds($seconds)->cascade()->forHumans(short: false, parts: 1);

        return $session->isRunning() && $session->paused_at === null
            ? "You have been working for {$words}."
            : "You had been working for {$words}.";
    }
}
