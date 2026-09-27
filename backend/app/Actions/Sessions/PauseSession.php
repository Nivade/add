<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Exceptions\InvalidSessionTransition;
use App\Models\ExecutionSession;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Pausing writes no outcome: the stretch is not finished, and the same row is resumed. */
final class PauseSession
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        return DB::transaction(function () use ($session): ExecutionSession {
            $session->lockOpen();

            if ($session->paused_at !== null) {
                throw new InvalidSessionTransition("Session {$session->id} is already paused.");
            }

            $session->update(['paused_at' => now()]);

            RecordExecutionEvent::run($session, ExecutionEventType::Paused);

            return $session;
        });
    }
}
