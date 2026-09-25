<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Exceptions\InvalidSessionTransition;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResumeSession
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        $session->assertOpen();

        if ($session->paused_at === null) {
            throw new InvalidSessionTransition("Session {$session->id} is not paused.");
        }

        $session->update(['paused_at' => null]);

        RecordExecutionEvent::run($session, ExecutionEventType::Resumed);

        return $session;
    }
}
