<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\ExecutionEventType;
use App\Enums\StepStatus;
use App\Models\ExecutionSession;
use App\Support\Execution\SessionState;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

final class CompleteStep
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        $step = SessionState::currentStep($session);

        return DB::transaction(function () use ($session, $step): ExecutionSession {
            $step->update(['status' => StepStatus::Done, 'completed_at' => now()]);

            $session->update(['steps_completed' => $session->steps_completed + 1]);

            RecordExecutionEvent::run($session, ExecutionEventType::StepCompleted, $step->id);

            return AdvanceSession::run($session);
        });
    }
}
