<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\CommitmentStatus;
use App\Enums\ExecutionEventType;
use App\Enums\StepStatus;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use Lorisleiva\Actions\Concerns\AsObject;

final class CompleteStep
{
    use AsObject;

    public function handle(ExecutionSession $session, string $stepId): ExecutionSession
    {
        return $session->transition(function () use ($session, $stepId): ExecutionSession {
            $step = $session->currentStepOrFail($stepId);

            $step->update(['status' => StepStatus::Done, 'completed_at' => now()]);

            Commitment::query()->open()->forStep($step->id)->update(['status' => CommitmentStatus::Kept]);

            $session->increment('steps_completed');

            RecordExecutionEvent::run($session, ExecutionEventType::StepCompleted, $step->id);

            return AdvanceSession::run($session);
        });
    }
}
