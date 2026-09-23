<?php

declare(strict_types=1);

namespace App\Support\Execution;

use App\Enums\StepStatus;
use App\Models\ExecutionSession;
use App\Models\Step;
use Illuminate\Contracts\Database\Query\Builder;

/** Counted from the rows, never from consecutive days, and stated without praise. */
final class ProgressLines
{
    /** @return list<string> */
    public static function for(ExecutionSession $session): array
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

        $today = self::doneToday($session);

        if ($today > 0) {
            $lines[] = $today.' '.($today === 1 ? 'thing' : 'things').' finished today.';
        }

        return $lines;
    }

    private static function doneToday(ExecutionSession $session): int
    {
        $midnight = $session->user->now()->startOfDay();

        return Step::query()
            ->where('status', StepStatus::Done)
            ->where('completed_at', '>=', $midnight)
            ->whereHas('intention', fn (Builder $intention) => $intention->where('user_id', $session->user_id))
            ->count();
    }
}
