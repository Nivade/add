<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

/** Ends nothing and scores nothing: it pauses, so coming back is a welcome rather than a clock that kept running. */
final class RecordDistraction
{
    use AsObject;

    public function handle(ExecutionSession $session, ?string $seenEventId = null): ExecutionSession
    {
        return $session->transition(function () use ($session, $seenEventId): ExecutionSession {
            if ($seenEventId !== null) {
                $session->assertSeen($seenEventId);
            }

            if ($session->paused_at === null) {
                $session->update(['paused_at' => now()]);
            }

            RecordExecutionEvent::run($session, ExecutionEventType::Distracted);

            return $session;
        });
    }
}
