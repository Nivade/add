<?php

declare(strict_types=1);

namespace App\Support\Execution;

use App\Models\ExecutionSession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;

/** §13: elapsed time stated in words, because a running clock is a countdown by another name. */
final class ElapsedWords
{
    /** Below this, "a minute or two" is truer than a number. */
    private const int FLOOR_SECONDS = 90;

    public static function for(ExecutionSession $session, ?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now($session->user->timezone);
        $until = $session->paused_at ?? $session->ended_at ?? $now;
        $seconds = (int) $session->started_at->diffInSeconds($until, absolute: true);

        if ($seconds < self::FLOOR_SECONDS) {
            return 'You have just started.';
        }

        $words = CarbonInterval::seconds($seconds)->cascade()->forHumans(short: false, parts: 1);

        return $session->paused_at === null
            ? "You have been working for {$words}."
            : "You had been working for {$words}.";
    }
}
