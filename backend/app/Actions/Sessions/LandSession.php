<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Enums\SessionOutcome;
use App\Models\ExecutionSession;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** The one place a session ends, so the guard cannot hold on one path and not the other. */
final class LandSession
{
    use AsObject;

    public function handle(ExecutionSession $session, SessionOutcome $outcome): ExecutionSession
    {
        return DB::transaction(function () use ($session, $outcome): ExecutionSession {
            $session->lockOpen();

            $lastStepId = $session->current_step_id;

            $session->update([
                'current_step_id' => null,
                'outcome' => $outcome,
                'ended_at' => now(),
            ]);

            RecordExecutionEvent::run($session, ExecutionEventType::Stopped, $lastStepId, [
                'outcome' => $outcome->value,
            ]);

            return $session;
        });
    }
}
