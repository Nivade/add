<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Ends nothing and scores nothing. The interruption is one row so returning can read it. */
final class RecordDistraction
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        return DB::transaction(function () use ($session): ExecutionSession {
            $session->lockOpen();

            RecordExecutionEvent::run($session, ExecutionEventType::Distracted);

            return $session;
        });
    }
}
