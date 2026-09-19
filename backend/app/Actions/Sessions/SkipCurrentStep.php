<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Actions\Steps\SkipStep;
use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use App\Support\Execution\SessionState;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Skip advances and records; it never marks the step or the person. */
final class SkipCurrentStep
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        $step = SessionState::currentStep($session);

        return DB::transaction(function () use ($session, $step): ExecutionSession {
            SkipStep::run($step);

            RecordExecutionEvent::run($session, ExecutionEventType::StepSkipped, $step->id);

            return AdvanceSession::run($session, $step->id);
        });
    }
}
