<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

/** Ends nothing and scores nothing. The interruption is one row so returning can read it. */
final class RecordDistraction
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        return $session->transition(function () use ($session): ExecutionSession {
            RecordExecutionEvent::run($session, ExecutionEventType::Distracted);

            return $session;
        });
    }
}
