<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Models\User;
use App\Support\Execution\RunningSession;
use Illuminate\Support\Facades\Cache;
use Lorisleiva\Actions\Concerns\AsObject;

/** A stretch of focused work is one row, and it points at the step the person asked for. */
final class StartSession
{
    use AsObject;

    public function handle(User $user, Step $step): ExecutionSession
    {
        return Cache::lock(RunningSession::lockKey($user), 10)->block(5, function () use ($user, $step): ExecutionSession {
            $running = RunningSession::forUser($user);

            return $running instanceof ExecutionSession
                ? $this->retarget($running, $user, $step)
                : $this->open($user, $step);
        });
    }

    /** Pressing start on something else is an answer to "what now", so the session follows rather than ignoring it. */
    private function retarget(ExecutionSession $running, User $user, Step $step): ExecutionSession
    {
        if ($running->current_step_id === $step->id) {
            return $running;
        }

        // A session belongs to one intention, so moving to another one closes this stretch and opens the next.
        if ($running->intention_id !== $step->intention_id) {
            StopSession::run($running);

            return $this->open($user, $step);
        }

        $running->update(['current_step_id' => $step->id]);

        RecordExecutionEvent::run($running, ExecutionEventType::Started, $step->id);

        return $running;
    }

    private function open(User $user, Step $step): ExecutionSession
    {
        $session = ExecutionSession::query()->create([
            'user_id' => $user->id,
            'intention_id' => $step->intention_id,
            'current_step_id' => $step->id,
            'started_at' => now(),
        ]);

        RecordExecutionEvent::run($session, ExecutionEventType::Started);

        return $session;
    }
}
