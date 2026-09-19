<?php

declare(strict_types=1);

namespace App\Actions\Sessions;

use App\Actions\Steps\SplitStep;
use App\Enums\ExecutionEventType;
use App\Enums\StuckReason;
use App\Models\ExecutionSession;
use App\Models\Step;
use App\Support\Execution\SessionState;
use Lorisleiva\Actions\Concerns\AsObject;

/** Every answer leaves the person with something to start or a clean stop. */
final class ReportStuck
{
    use AsObject;

    public function handle(ExecutionSession $session, StuckReason $reason, ?string $note = null): ExecutionSession
    {
        $step = SessionState::currentStep($session);

        RecordExecutionEvent::run($session, ExecutionEventType::Stuck, $step->id, [
            'reason' => $reason->value,
            'note' => $note,
        ]);

        return match (true) {
            $reason->wantsSmallerStep() => $this->makeItSmaller($session, $step),
            $reason === StuckReason::NeedSomething,
            $reason === StuckReason::NotEnoughInformation => AdvanceSession::run($session, $step->id),
            $reason === StuckReason::Tired,
            $reason === StuckReason::DontWantTo => StopSession::run($session),
            default => $session,
        };
    }

    /** The move is deterministic and immediate; the split lands whenever the model answers. */
    private function makeItSmaller(ExecutionSession $session, Step $step): ExecutionSession
    {
        SplitStep::dispatch($step);

        $shortest = $session->intention->remainingSteps()
            ->whereKeyNot($step->id)
            ->orderBy('estimated_seconds')
            ->orderBy('position')
            ->first();

        if ($shortest instanceof Step) {
            $session->update(['current_step_id' => $shortest->id]);
        }

        return $session;
    }
}
