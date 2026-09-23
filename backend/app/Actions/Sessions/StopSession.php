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

    public function handle(ExecutionSession $session): ExecutionSession
    {
        return LandSession::run($session, SessionOutcome::Stopped);
    }
}
