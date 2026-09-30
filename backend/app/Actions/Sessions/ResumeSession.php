<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

/** Acknowledging a return is valid on a running session too; only a paused one has anything to clear. */
final class ResumeSession
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        return $session->transition(function () use ($session): ExecutionSession {
            if ($session->paused_at !== null) {
                $session->update(['paused_at' => null]);
            }

            RecordExecutionEvent::run($session, ExecutionEventType::Resumed);

            return $session;
        });
    }
}
