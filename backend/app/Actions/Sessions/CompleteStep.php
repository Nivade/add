<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Enums\CommitmentStatus;
use App\Enums\ExecutionEventType;
use App\Enums\StepStatus;
use App\Models\Commitment;
use App\Models\ExecutionSession;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

final class CompleteStep
{
    use AsObject;

    public function handle(ExecutionSession $session): ExecutionSession
    {
        $step = $session->currentStepOrFail();

        return DB::transaction(function () use ($session, $step): ExecutionSession {
            $step->update(['status' => StepStatus::Done, 'completed_at' => now()]);

            Commitment::query()->open()->forStep($step->id)->update(['status' => CommitmentStatus::Kept]);

            $session->increment('steps_completed');

            RecordExecutionEvent::run($session, ExecutionEventType::StepCompleted, $step->id);

            return AdvanceSession::run($session);
        });
    }
}
