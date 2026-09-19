<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Enums\SessionOutcome;
use App\Models\ExecutionSession;
use App\Support\Execution\SessionState;
use Lorisleiva\Actions\Concerns\AsObject;

/** Stopping is a real answer. It carries the outcome `stopped`, which is not a failure word. */
final class StopSession
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        SessionState::assertOpen($session);

        $lastStepId = $session->current_step_id;

        $session->update([
            'current_step_id' => null,
            'outcome' => SessionOutcome::Stopped,
            'ended_at' => now(),
        ]);

        RecordExecutionEvent::run($session, ExecutionEventType::Stopped, $lastStepId, [
            'outcome' => SessionOutcome::Stopped->value,
        ]);

        return $session;
    }
}
