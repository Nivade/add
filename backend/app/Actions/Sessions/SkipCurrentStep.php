<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Actions\Steps\SkipStep;
use App\Enums\ExecutionEventType;
use App\Models\ExecutionSession;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

/** Skip advances and records; it never marks the step or the person. */
final class SkipCurrentStep
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        return DB::transaction(function () use ($session): ExecutionSession {
            $session->lockOpen();

            $step = $session->currentStepOrFail();

            SkipStep::run($step);

            RecordExecutionEvent::run($session, ExecutionEventType::StepSkipped, $step->id);

            return AdvanceSession::run($session, $step->id);
        });
    }
}
