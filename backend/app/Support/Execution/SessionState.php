<?php

declare(strict_types=1);

namespace App\Support\Execution;

use App\Exceptions\InvalidSessionTransition;
use App\Models\ExecutionSession;
use App\Models\Step;

/** Invalid transitions throw here rather than no-opping in eight actions. */
final class SessionState
{
    public static function assertOpen(ExecutionSession $session): void
    {
        if (! $session->isRunning()) {
            throw new InvalidSessionTransition("Session {$session->id} has already ended.");
        }
    }

    public static function currentStep(ExecutionSession $session): Step
    {
        self::assertOpen($session);

        $step = $session->currentStep()->getResults();

        if (! $step instanceof Step) {
            throw new InvalidSessionTransition("Session {$session->id} is not pointing at a step.");
        }

        return $step;
    }
}
