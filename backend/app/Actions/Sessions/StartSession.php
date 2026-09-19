<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsObject;

/** A stretch of focused work is one row: a running session is returned, never doubled. */
final class StartSession
{
    use AsObject;

    public function handle(User $user, Step $step): ExecutionSession
    {
        $running = ExecutionSession::query()->where('user_id', $user->id)->running()->first();

        if ($running instanceof ExecutionSession) {
            return $running;
        }

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
