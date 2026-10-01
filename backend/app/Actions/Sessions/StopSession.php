<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\SessionOutcome;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

/** Stopping is a real answer. It carries the outcome `stopped`, which is not a failure word. */
final class StopSession
{
    use AsObject;

    /** Null only for the app's own callers; a person's tap always says what it saw. */
    public function handle(ExecutionSession $session, ?string $seenEventId = null): ExecutionSession
    {
        return $session->transition(function () use ($session, $seenEventId): ExecutionSession {
            if ($seenEventId !== null) {
                $session->assertSeen($seenEventId);
            }

            return LandSession::run($session, SessionOutcome::Stopped);
        });
    }
}
